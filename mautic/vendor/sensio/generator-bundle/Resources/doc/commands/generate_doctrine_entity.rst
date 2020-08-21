Generating a New Doctrine Entity Stub
=====================================

.. caution::

    If your application is based on Symfony 2.x version, replace ``php bin/console``
    with ``php app/console`` before executing any of the console commands included
    in this article.

Usage
-----

The ``generate:doctrine:entity`` command generates a new Doctrine entity stub
including the mapping definition and the class properties, getters and setters.

By default, the command is run in the interactive mode and asks questions to
determine the bundle name, location, configuration format and default structure:

.. code-block:: bash

    $ php bin/console generate:doctrine:entity

To deactivate the interactive mode, use the ``--no-interaction`` option or its
alias ``-n``, but don't forget to pass all needed options:

.. code-block:: bash

    $ php bin/console generate:doctrine:entity AcmeBlogBundle:Post -n --fields="title:string(100) body:text" --format=xml

Arguments
---------

``entity``
    The entity name given as a shortcut notation containing the bundle name
    in which the entity is located and the name of the entity (for example,
    ``AcmeBlogBundle:Post``):

    .. code-block:: bash

        $ php bin/console generate:doctrine:entity AcmeBlogBundle:Post

Available Options
-----------------

``--entity``

    .. caution::

        This option has been deprecated in version 3.0, and will be removed in 4.0.
        Pass it as argument instead.

    The entity name given as a shortcut notation containing the bundle name
    in which the entity is located and the name of the entity (for example,
    ``AcmeBlogBundle:Post``):

    .. code-block:: bash

        $ php bin/console generate:doctrine:entity --entity=AcmeBlogBundle:Post

``--fields``
    The list of fields to generate in the entity class:

    .. code-block:: bash

        $ php bin/console generate:doctrine:entity --fields="title:string(length=100 nullable=true unique=false) body:text ranking:decimal(precision=10 scale=0)"

    .. versionadded:: 3.0
        Ability to pass named options to fields was added in version 3.0.
        Previously, only the ``string`` type was allowed to receive the length
        value as argument. Available options are ``length``, ``nullable``,
        ``unique``, ``precision`` and ``scale``.

``--format``
    **allowed values**: ``annotation|php|yml|xml`` **default**: ``annotation``

    This option determines the format to use for the generated Doctrine entity
    mapping configuration files. By default, the command uses the ``annotation``
    format:

    .. code-block:: bash

        $ php bin/console generate:doctrine:entity --format=annotation
