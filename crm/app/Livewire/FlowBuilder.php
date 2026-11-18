<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Flow;
use App\Models\FlowNode;
use App\Models\FlowEdge;

class FlowBuilder extends Component
{
    public $flows;
    public $activeFlowId = null;

    // Node Form
    public $nodeName = '';
    public $nodeType = 'template';
    public $nodeTemplateName = '';
    public $nodeTextContent = '';
    public $nodeExpectedLeadField = '';
    public $nodeTriggersHumanHandover = false;

    public function mount()
    {
        $this->loadFlows();
    }

    public function loadFlows()
    {
        $this->flows = Flow::with(['nodes.edges', 'nodes.botMessage'])->get();
    }

    public function createFlow()
    {
        $flow = Flow::create([
            'name' => 'New Automated Flow',
            'is_active' => true,
        ]);
        
        $this->activeFlowId = $flow->id;
        $this->loadFlows();
    }

    public function selectFlow($id)
    {
        $this->activeFlowId = $id;
    }

    public function addNode()
    {
        $this->validate([
            'nodeName' => 'required|string',
            'nodeType' => 'required|in:template,text,action'
        ]);

        FlowNode::create([
            'flow_id' => $this->activeFlowId,
            'name' => $this->nodeName,
            'type' => $this->nodeType,
            'template_name' => $this->nodeTemplateName,
            'text_content' => $this->nodeTextContent,
            'expected_lead_field' => $this->nodeExpectedLeadField,
            'triggers_human_handover' => $this->nodeTriggersHumanHandover
        ]);

        $this->resetNodeForm();
        $this->loadFlows();
    }

    public function resetNodeForm()
    {
        $this->nodeName = '';
        $this->nodeType = 'template';
        $this->nodeTemplateName = '';
        $this->nodeTextContent = '';
        $this->nodeExpectedLeadField = '';
        $this->nodeTriggersHumanHandover = false;
    }

    // A simplified edge addition for demo purposes
    public function addEdge($sourceNodeId, $targetNodeId, $condition, $saveValue = null)
    {
        FlowEdge::create([
            'source_node_id' => $sourceNodeId,
            'target_node_id' => $targetNodeId,
            'condition_payload' => $condition,
            'save_value' => $saveValue
        ]);
        $this->loadFlows();
    }

    public function render()
    {
        return view('livewire.flow-builder')->layout('layouts.app');
    }
}
