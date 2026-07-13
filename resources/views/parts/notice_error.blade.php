{{ "{$assign['exception']->getFile()}#{$assign['exception']->getLine()}" }}<br><br>
500 Error: {!! nl2br(e($assign['exception']->getMessage())) !!}<br><br>
Stack Trace: <pre>{!! nl2br(e($assign['exception']->getTraceAsString())) !!}</pre>
