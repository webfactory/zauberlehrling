zauberlehrling
==============

A collection of tools and ideas for splitting up a big monolithic PHP application in smaller parts, i.e. smaller
applications and microservices. It contains console commands for identifying potentially unused PHP files, Composer
packages and MySQL tables.

The name "zauberlehrling" derives from [the famous poem by Johann Wolfgang von Goethe](https://en.wikipedia.org/wiki/The_Sorcerer%27s_Apprentice)
(you may have also seen the iconic cartoon "Fantasia" by Walt Disney). In these tales, a sorcerer's apprentice splits up
a magical, out of control broom with an axe. Unfortunately for him, each piece has a life of it's own and only
multiplies the problem.


Installation
------------

    git clone https://github.com/webfactory/zauberlehrling.git
    cd zauberlehrling
    composer install


Splitting up the monolith
-------------------------

### Your local development environment

At this point, you probably know your monolith way to well. You've fixed devious bugs and if you're brave/ruthless
enough, you might even have added a feature. So I guess you've set up your local development environment already.

Just a tip: During the split, you might wish to do several dumps of your production database. Consider
[slimdump](https://github.com/webfactory/slimdump) for storing configurations. These configurations are really handy,
as they can be shared among your coworkers and provide neat features. E.g. you can ignore more and more tables that
emerged to be irrelevant for your extracted application; you can also ignore BLOB columns or dump only rows matching
certain conditions for speeding up the dump process. And you can easily anonymize personalized data to protect your
customers.


### Determine used PHP files

To determine the used PHP files, I suggest writing black box tests for each use case of your application and collect the
code coverage information during their execution.

For the black box tests, e.g. you could write [behat](http://behat.org/) tests for
 
* requesting the homepage
* log in of a user
* send a search form and retrieve results
* create, edit an delete an entity
* request a page without proper permissions
* ...

Now for the code coverage part. Most frameworks provide a frontcontroller, e.g. for Symfony it's
```web/symfony-webapp.php```. If you have xdebug installed, you can write at the beginning of such a frontcontroller:

    <?php
    xdebug_start_code_coverage();

and at it's end something like this:

    <?php
    $filePointer = fopen($outputFile, 'a');
    foreach (array_keys(xdebug_get_code_coverage()) as $usedFileName) {
        fwrite($filePointer, $usedFileName . PHP_EOL);
    }
    fclose($filePointer);

Now, when you execute your behat tests, all executed files will be written to ```$outputFile```. I don't recommend
executing your unit tests now, as these tests could cover code never used in production.  

You can consolidate this file (removing duplicates and sort the file nameslist) with

    bin/console consolidate-used-files usedFiles

where the ```usedFiles``` argument is the path to the file containing the list of used files. It will be overwritten
with it's consolidated version.


### Unused PHP files

    bin/console show-unused-php-files pathToInspect pathToIgnore usedFiles pathToOutput

With these parameters:

* pathToInspect: path to the directory to search for PHP files
* pathToIgnore: path to ignore when searching for PHP files, e.g. a temp directory
* usedFiles: path to a file containing the list of used files (see [Determine used PHP files](#determine-used-php-files))
* pathToOutput: path to a file where the list of unused files should be dumped


### Unused Composer packages

    bin/console show-unused-composer-packages [--vendorDir=...] composerJson usedFiles

With these parameters:

* composerJson: path to the composer.json of the project to analyze 
* usedFiles: path to a file containing the list of used files (see [Determine used PHP files](#determine-used-php-files))

And this option:

* vendorDir: path to the vendor directory of the project to analyze. Defaults to the directory of the composer.json + '/vendor'.


### Unused MySQL Tables

    bin/console show-unused-mysql-tables


Credits, Copyright and License
------------------------------

This bundle was started at webfactory GmbH, Bonn.

- <http://www.webfactory.de>
- <http://twitter.com/webfactory>

Copyright 2016-2017 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).
