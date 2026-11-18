<?php
$n = App\Models\FlowNode::where('name', 'Welcome Message')->first();
if($n) {
    $n->template_name = 'first_message';
    $n->save();
}

echo "Done";
