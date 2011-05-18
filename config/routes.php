<?php
get('/', 'default#index', 'homepage');
get('/users', 'user#index', 'users');
get('/error', 'user#error', 'users_error');
