<?php
class UserController extends RootController{
  public function index() {
    v('title', 'Users List');
    render();
  }
}
