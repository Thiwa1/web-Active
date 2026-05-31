<?php
// We will test the validation logic by intercepting the header call

// Create a custom php.ini to define header as a mocked function if possible? No, we can't redefine built-in functions.
// Instead, let's run a small built-in server and hit the endpoint.
