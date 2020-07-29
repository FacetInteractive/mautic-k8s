# Stack/Run

Shortcut function for handling HttpKernel front-controller boilerplate.

## Example

Usually you need to put all of that `$request = ... && $response =
$app->handle($request)` business in the front controller yourself. No more!
With Stack/Run, you just need to call one function:

```php
$app = ...;

Stack\run($app);
```
