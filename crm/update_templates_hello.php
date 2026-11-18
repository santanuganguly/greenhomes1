<?php
$n = App\Models\FlowNode::where('name', 'Welcome Message')->first();
if($n) {
    $n->template_name = 'hello_world';
    $n->save();
}

echo "Done";
