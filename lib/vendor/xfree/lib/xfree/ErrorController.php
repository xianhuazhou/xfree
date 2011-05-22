<?php
namespace xfree;
class ErrorController extends Controller {
    public function render500Action() { 
        //header('HTTP/1.1 500 Error');
        $this->renderError(500);
    }

    public function render404Action() {
        //header('HTTP/1.1 404 Not Found');
        $this->renderError(404);
    }

    protected function renderError($status) {
        $e = v('x.exception.object');
        echo '<!DOCTYPE html><html><body>';
        echo "<h1> $status Error</h2>";
        echo '<h2>Exception: ' . $e->getMessage() . '</h2>';
        echo '<hr>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
        echo '<hr>by xfree';
        echo '</body></html>';
    }
}
