<?php

$flow = App\Models\Flow::create([
    'name' => 'Default Welcome Flow', 
    'is_active' => true, 
    'is_default_welcome' => true
]); 

$node1 = App\Models\FlowNode::create([
    'flow_id' => $flow->id, 
    'name' => 'Welcome Message', 
    'type' => 'template', 
    'template_name' => 'welcome_template', 
    'expected_lead_field' => 'business_vertical'
]); 

$node2 = App\Models\FlowNode::create([
    'flow_id' => $flow->id, 
    'name' => 'Ask for Budget', 
    'type' => 'template', 
    'template_name' => 'ask_budget', 
    'expected_lead_field' => 'budget'
]); 

$node3 = App\Models\FlowNode::create([
    'flow_id' => $flow->id, 
    'name' => 'Talk to Expert', 
    'type' => 'action', 
    'triggers_human_handover' => true
]); 

App\Models\FlowEdge::create([
    'source_node_id' => $node1->id, 
    'target_node_id' => $node2->id, 
    'condition_payload' => 'GREEN_HOMES', 
    'save_value' => 'Green Homes'
]); 

App\Models\FlowEdge::create([
    'source_node_id' => $node1->id, 
    'target_node_id' => $node3->id, 
    'condition_payload' => 'TALK_EXPERT', 
    'save_value' => null
]);

echo "Flow seeded successfully!\n";
