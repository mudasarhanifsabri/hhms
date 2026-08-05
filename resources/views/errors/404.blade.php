@include('errors.layout', [
    'statusCode' => 404,
    'title' => 'Page not found',
    'message' => 'This page is not available. Please check the link or contact your IT expert.',
])
