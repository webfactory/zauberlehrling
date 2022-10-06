zauberlehrling
==============

A collection of tools and ideas for splitting up a big monolithic PHP application in smaller parts, i.e. smaller
applications and microservices. It contains console commands for identifying potentially unused PHP files, Composer
packages, MySQL tables and public web assets.

The name "zauberlehrling" derives from [the famous poem by Johann Wolfgang von Goethe](https://en.wikipedia.org/wiki/The_Sorcerer%27s_Apprentice)
(you may have also seen the iconic cartoon "Fantasia" by Walt Disney). In these tales, a sorcerer's apprentice splits up
a magical, out of control broom with an axe. Unfortunately for him, each piece has a life of it's own and only
multiplies the problem.


Installation
------------

    git clone https://github.com/webfactory/zauberlehrling.git
    cd zauberlehrling
    composer install

When asked for the database parameters, provide the information for your local database of the monolith. If your
monolith has no database or you don't want any help with it, stay with the default parameters.


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


### Greenfield or Brownfield?

The answer to this question seems to depend mostly on the amount of code you want to reuse. If you know you want to
replace e.g. an old integrated messaging system with a shiny new microservice (i.e. a partial rewrite of the monolith),
you'll probably be fine with a greenfield project with your best and latest technology.

But if you just want to split up the monolith and you're afraid of hidden dependencies, or if you want to keep down your
effort and rewrite only what's necessary: my guess is you'll be better off with a brownfield project. Clone the
monolith's repository to keep it's history of commit messages. I find it there is often much knowledge in these messages
and linked ticket systems. Sometimes they're the only chance to get an understanding for the reasoning of a particular
crazy piece of code.

Then, get rid of everything you don't need in your extracted application. The following chapters may help.

Also, my advice is to keep a separate local working copy of the monolith. Sooner or later you'll probably encounter an
error you cannot pinpoint to one of your refactorings, or you notice you've deleted too much and you cannot restore it
easily from your VCS. In this cases, you'll be happy to have a quick look into the working monolith. 


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

Depending on your project you may want to assert different things. In my experience, the following assertions were often
helpful: 

* correct URL (i.e. the user is not being redirected e.g. due to authorization problems)
* HTTP status code being 200 (i.e. the user got no fancy error page)
* text content like "x was saved in the database" (to detect failures after form submission) - may seem brittle for a
  test, but I don't expect that message to be changed while you're extracting your microservice.  

Now for the code coverage part. Most frameworks provide a frontcontroller, e.g. for Symfony it's
```web/symfony-webapp.php```. If you have xdebug installed, you can write at the beginning of such a frontcontroller:

```php
xdebug_start_code_coverage();
```

and at it's end something like this:

```php
$filePointer = fopen($outputFile, 'ab');
fwrite($filePointer, implode(PHP_EOL, array_keys(xdebug_get_code_coverage())));
fclose($filePointer);
```

Now, when you execute your behat tests, all executed PHP files will be written to ```$outputFile```. I don't recommend
executing your unit tests now, as these tests could cover code never used in production.  

You're in no way restricted to xdebug for collecting your coverage. E.g., you could also do some Aspect Oriented Programming (AOP) magic, just remember it may have more advanced requirements than your monolith runtime environment can fulfill. Another idea is utilizing [sysdig](https://www.sysdig.org/) or some other form of file system monitoring.

File system monitoring tools can be tricky to use:

- You have to make sure the file system access you wish to log are done in reality. If the file system access is cached away by some shady component in your environment, you won't get all used files (false negatives).
- Some tools like to index all files - e.g. for a desktop search or your IDE for static code analysis. You have to stop them from opening all files during your logging session or you will get too many results (false positives).

But if you manage to set up everything fine, file system monitoring tools have one big advantage: they're not restricted to logging executed PHP files, but can report accessed files of all sorts, e.g. configuration files. That improves the detection of (un)used packages.

For sysdig, you might want to try:

    sudo sysdig -p "%fd.name" evt.type=open |grep "/your/project/" |grep -v "/your/project/tmp/" |grep -v "/your/project/log/" > used-files.txt


You can consolidate this file (removing duplicates and sort the file names list) with

    bin/console consolidate-used-files usedFiles

where the ```usedFiles``` argument is the path to the file containing the list of used files. It will be overwritten
with it's consolidated version.


### Unused PHP files

    bin/console show-unused-php-files [--pathToInspect=...] [--pathToOutput=...] [--pathToBlacklist=...] usedFiles

With this argument:

* ```usedFiles```: Path to a file containing the list of used files (see [Determine used PHP files](#determine-used-php-files))

and these options:

* ```-p```, ```--pathToInspect```: Path to the directory to search for PHP files. If not set, it will be determined as
  the common parent path of the used files.
* ```-o```, ```--pathToOutput```: Path to the output file. If not set, it will be "potentially-unused-files.txt" next to
  the file named in the usedFiles argument.
* ```-b```, ```--pathToBlacklist```: Path to a file containing a blacklist of regular expressions to exclude from the
  output. The blacklist may grow over time. At first, you might want to exclude temp directories and libraries. But as
  you inspect the list of potentially unused files, you may notice some file definitely needed by your application,
  although the usage is not detected by your tests. You can persist such insights in this blacklist.
   
  The file should contain one regular expression per line, e.g.:
 
      #/var/www/my-project/features/.*# 
      #/var/www/my-project/tmp/.*# 
      #/var/www/my-project/vendor/.*# 
      #/var/www/my-project/file-only-used-in-production-environment.php# 


### Unused Composer packages

    bin/console show-unused-composer-packages [--vendorDir=...] composerJson usedFiles

With these arguments:

* ```composerJson```: path to the composer.json of the project to analyze 
* ```usedFiles```: path to a file containing the list of used files (see [Determine used PHP files](#determine-used-php-files))

And these options:

* ```-l```, ```vendorDir```: path to the vendor directory of the project to analyze. Defaults to the directory of the composer.json + '/vendor'.
* ```-b```, ```--pathToBlacklist``` Path to a file containing a blacklist of regular expressions to exclude from the output (see [Unused PHP files](#unused-php-files) for details).


### Unused Public Assets

    bin/console show-unused-public-assets [--regExpToFindFile=...] [--pathToOutput=...] [--pathToBlacklist=...] pathToPublic pathToLogFile

With these arguments:

* ```pathToPublic```: Path to the public web root of your project.
* ```pathToLogFile```: Path to the web server's access log file.

And these options:

* ```-r```, ```--regExpToFindFile``` Regular expression for the log file capturing the path of the accessed file as it's first capture group. Defaults to ```#"(?:get|post) ([a-z0-9\_\-\.\/]*)#i```.
* ```-o```, ```--pathToOutput``` Path to the output file. If not set, it will be "potentially-unused-public-assets.txt" in the folder above the public web root.
* ```-b```, ```--pathToBlacklist``` Path to a file containing a blacklist of regular expressions to exclude from the output (see [Unused PHP files](#unused-php-files) for details).


### Unused MySQL Tables

So, you've cloned your code base, and you have probably copied your database as well. How do you find the unused tables? 

The idea is analogous to the code coverage. First, enable logging in MySQL and possibly delete old log date, e.g. with

```mysql
SET global general_log = 1;
SET global log_output = 'table';
TRUNCATE mysql.general_log;
```

Then execute your tests for all use cases of your application. Afterwards, you can disable MySQL logging with

```mysql
SET global general_log = 0;
```

Finally, call the following console command:

    bin/console show-unused-mysql-tables


Credits, Copyright and License
------------------------------

This bundle was started at webfactory GmbH, Bonn.

- <http://www.webfactory.de>
- <http://twitter.com/webfactory>

Copyright 2016-2017 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).
