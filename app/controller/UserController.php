<?php
use xfree\Controller;

class UserController extends Controller{
  public function indexAction() {
    v('title', 'Users List');
    render();
  }
}
