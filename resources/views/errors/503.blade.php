@include('errors.layout', [
    'statusCode' => 503,
    'title' => 'Service temporarily unavailable',
    'message' => 'The system is temporarily unavailable. Please contact your IT expert if this continues.',
])
