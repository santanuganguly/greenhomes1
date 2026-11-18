<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Models\Conversation;
use App\Jobs\SendCampaignMessageJob;
use App\Services\WhatsAppService;
use Tests\TestCase;

class MarketingCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock env vars for WhatsAppService
        putenv('META_ACCESS_TOKEN=fake_token');
        putenv('META_PHONE_NUMBER_ID=123456789');
        putenv('META_WABA_ID=987654321');
    }

    public function test_whatsapp_service_fetches_templates_correctly()
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/987654321/message_templates*' => Http::response([
                'data' => [
                    ['name' => 'hello_world', 'language' => 'en', 'status' => 'APPROVED'],
                ]
            ], 200)
        ]);

        $service = new WhatsAppService();
        $templates = $service->getTemplates();

        $this->assertCount(1, $templates);
        $this->assertEquals('hello_world', $templates[0]['name']);
    }

    public function test_send_campaign_job_handles_success()
    {
        Queue::fake();

        Http::fake([
            'https://graph.facebook.com/v19.0/*/messages*' => Http::response([
                'messages' => [['id' => 'wamid.HBgL']]
            ], 200)
        ]);

        $campaign = Campaign::create([
            'name' => 'Ronittest Campaign',
            'template_name' => 'hello_world',
            'status' => 'running',
            'total_recipients' => 1
        ]);

        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'phone' => '919876543210',
            'status' => 'queued'
        ]);

        $job = new SendCampaignMessageJob($recipient);
        $job->handle(new WhatsAppService());

        $recipient->refresh();
        $campaign->refresh();

        if ($recipient->status !== 'sent') {
            dump($recipient->error_message);
        }

        $this->assertEquals('sent', $recipient->status);
        $this->assertNotNull($recipient->meta_message_id);
        $this->assertEquals(1, $campaign->sent_count);
    }

    public function test_send_campaign_job_handles_rate_limit_backoff()
    {
        Queue::fake();

        Http::fake([
            'https://graph.facebook.com/v19.0/*/messages*' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded',
                    'code' => 130429
                ]
            ], 400)
        ]);

        $campaign = Campaign::create([
            'name' => 'Ronittest Rate Limit',
            'template_name' => 'hello_world',
            'status' => 'running',
            'total_recipients' => 1
        ]);

        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'phone' => '919876543210',
            'status' => 'queued',
            'retry_count' => 0
        ]);

        $job = new SendCampaignMessageJob($recipient);
        $job->handle(new WhatsAppService());

        $recipient->refresh();

        if ($recipient->status !== 'queued') {
            dump($recipient->error_message);
        }

        // Status should be queued again due to backoff
        $this->assertEquals('queued', $recipient->status);
        $this->assertEquals(1, $recipient->retry_count);
        
        // Assert job was re-dispatched
        Queue::assertPushed(SendCampaignMessageJob::class);
    }

    public function test_send_campaign_job_handles_permanent_error()
    {
        Queue::fake();

        Http::fake([
            'https://graph.facebook.com/v19.0/*/messages*' => Http::response([
                'error' => [
                    'message' => 'Invalid phone number',
                    'code' => 131026
                ]
            ], 400)
        ]);

        $campaign = Campaign::create([
            'name' => 'Ronittest Permanent Error',
            'template_name' => 'hello_world',
            'status' => 'running',
            'total_recipients' => 1,
            'failed_count' => 0
        ]);

        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'phone' => '919876543210',
            'status' => 'queued',
            'retry_count' => 0
        ]);

        $job = new SendCampaignMessageJob($recipient);
        $job->handle(new WhatsAppService());

        $recipient->refresh();
        $campaign->refresh();

        $this->assertEquals('failed', $recipient->status);
        $this->assertEquals(1, $campaign->failed_count);
        
        // Assert job was NOT re-dispatched for permanent error
        Queue::assertNotPushed(SendCampaignMessageJob::class);
    }

    public function test_opt_out_logic_in_bot_engine()
    {
        $conversation = Conversation::create(['phone_number' => '919876543210']);
        $lead = Lead::create(['phone_number' => '919876543210', 'conversation_id' => $conversation->id, 'marketing_opt_out' => false]);

        Http::fake([
            'https://graph.facebook.com/v19.0/*/messages*' => Http::response([], 200)
        ]);

        $engine = new \App\Services\BotEngine(new WhatsAppService());
        
        $messageData = [
            'from' => '919876543210',
            'id' => 'msg_123',
            'type' => 'text',
            'text' => ['body' => 'STOP']
        ];

        $engine->processMessage($messageData, []);

        $lead->refresh();
        $this->assertTrue((bool)$lead->marketing_opt_out);
        $this->assertNotNull($lead->opted_out_at);
    }
}
