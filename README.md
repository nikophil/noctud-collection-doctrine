# Noctud Collection Doctrine

Doctrine ORM bridge for [Noctud Collection](https://github.com/noctud/collection): expose to-many associations as Noctud collections.

> [!WARNING]
> This package relies on the custom collection classes proposed in [doctrine/orm#12777](https://github.com/doctrine/orm/pull/12777), which is not merged yet. Until it is released, `composer.json` installs Doctrine ORM from the branch of that pull request.

```shell
composer require noctud/collection-doctrine
```

Requires **PHP 8.4+**, `noctud/collection` 0.2 and Doctrine ORM 3.8.
