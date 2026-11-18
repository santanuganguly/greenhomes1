<?php
$n = App\Models\FlowNode::where('name', 'Welcome Message')->first();
if($n) {
    $n->template_name = 'first_message';
    $n->save();
}

$n2 = App\Models\FlowNode::where('name', 'Ask for Budget')->first();
if($n2) {
    $n2->template_name = 'second';
    $n2->save();
}
echo "Done";
