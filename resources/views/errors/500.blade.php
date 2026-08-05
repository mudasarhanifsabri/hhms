@include('errors.layout', [
    'statusCode' => 500,
    'title' => 'System issue',
    'message' => 'There is an issue. Please contact your IT expert.',
])
