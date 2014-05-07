ohanzee-router
==============

Router component for Ohanzee

Routes are used to determine the controller and action for a requested URI.
Every route generates a regular expression which is used to match a URI
and a route. Routes may also contain keys which can be used to set the
controller, action, and parameters.

Each <key> will be translated to a regular expression using a default
regular expression pattern. You can override the default pattern by providing
a pattern for the key:

    // This route will only match when <id> is a digit
    Route::set('user', 'user/<action>/<id>', array('id' => '\d+'));

    // This route will match when <path> is anything
    Route::set('file', '<path>', array('path' => '.*'));

It is also possible to create optional segments by using parentheses in
the URI definition:

    // This is the standard default route, and no keys are required
    Route::set('default', '(<controller>(/<action>(/<id>)))');

    // This route only requires the <file> key
    Route::set('file', '(<path>/)<file>(.<format>)', array('path' => '.*', 'format' => '\w+'));

Routes also provide a way to generate URIs (called "reverse routing"), which
makes them an extremely powerful and flexible way to generate internal links.