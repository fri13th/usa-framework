<?

class SimpleAuthMiddleware extends UsaMiddleware {
    // set up
    private $options;

    function setup($options) {
        $this->options = $options;
    }

    function process_request() {
        error_log("process");
        // return some urls.. by patterns
        
    }

    function process_response() {
        // do nothing
    }
}