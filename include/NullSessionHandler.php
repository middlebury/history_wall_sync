<?php
/**
 * @file The NullSessionHandler will not save any data and can be used in cases
 * where applications try to start a session, but really don't need to.
 */
class NullSessionHandler implements SessionHandlerInterface {

  public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
        return '';
    }

    public function write($id, $data) {
        return true;
    }

    public function destroy($id) {
        return true;
    }

    public function gc($maxlifetime) {
        return true;
    }
}
