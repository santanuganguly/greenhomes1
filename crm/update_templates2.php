<?php
$n = App\Models\FlowNode::where('name', 'Welcome Message')->first();
if($n) {
    $n->template_name = 'ghg_and_gh_welcome';
    $n->save();
}

$n2 = App\Models\FlowNode::where('name', 'Ask for Budget')->first();
if($n2) {
    $n2->template_name = 'sequence_2026_02_27_123154';
    $n2->save();
}
echo "Done";
