# UPGRADE

### 2.0 to 3.0

 * PHP has been bumped to 5.6.
 * Symfony has been bumped to 2.7.
 
### 1.1 to 2.0

 * All protected properties and methods have been updated to private except for entry points. This is mostly motivated
   for enforcing the encapsulation and easing backward compatibility.

### 1.0 to 1.1

The `Ivory\OrderedForm\Orderer\FormOrdererFactory` and `Ivory\OrderedForm\Orderer\FormFactory` has been removed as it
does not bring any value (except instantiate multiple form order which is not needed).

In order to only share a single instance of the form orderer, the
`Ivory\OrderedForm\ResolvedFormTypeFactory::$ordererFactory` has been renamed to
`Ivory\OrderedForm\ResolvedFormTypeFactory::$orderer` which now represents an
`Ivory\OrderedForm\Orderer\FormOrdererInterface`. Accordingly, the
`Ivory\OrderedForm\ResolvedFormTypeFactory::__construct` has been updated to take an optional
`Ivory\OrderedForm\Orderer\FormOrdererInterface`.

The `Ivory\OrderedForm\Orderer\FormOrderer::$form` has been removed as it is not needed in order to order form children
and will simplify the PHP garbage collection. Accordingly, the `Ivory\OrderedForm\Orderer\FormOrderer::reset` argument
has been removed.

The `Ivory\OrderedForm\Orderer\FormOrderer::detectedSymetricDiffered` has been renamed to
`Ivory\OrderedForm\Orderer\FormOrderer::detectedSymmetricDiffered`.
