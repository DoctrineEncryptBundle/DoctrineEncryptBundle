This demo-installation demonstrates a simple symfony 7.x-application using only attributes and Doctrine 3.x. 

Annotations are no longer allowed in symfony 7.x

# How to use
```shell
# run `composer install` in parent-directory to create symlinks for shared files
cd ../
composer install
cd -

# Install packages
composer install

# Serve the application
## Using symfony-cli
symfony serve
## Using php-builtin-server ()
php -S localhost:8000 -t public
## Other possibility
### Use a webserver like apache or nginx


# Run unittests
vendor/bin/simple-phpunit
```


