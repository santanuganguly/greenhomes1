<?php

$node = App\Models\FlowNode::where('name', 'Welcome Message')->first();
if ($node) {
    $node->template_name = 'ghg_and_gh_welcome';
    $node->save();
    echo "Updated Welcome Message template to ghg_and_gh_welcome\n";
}

$node2 = App\Models\FlowNode::where('name', 'Ask for Budget')->first();
if ($node2) {
    $node2->template_name = 'sequence_2026_02_27_123154'; // The budget template found in Meta
    $node2->save();
    echo "Updated Ask for Budget template to sequence_2026_02_27_123154\n";
}

echo "Done.\n";
