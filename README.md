# webfoto-php-core

This is the php core code of the project "Webfoto".

## The project

The "Webfoto" project's purpose is to put some cameras, that take a photo every a certain amount of time, in some hotels, so that a web component can show the timeline of the view of that hotel in the hotel's website.

## The core code

There are both a **php backend**, that could be used with php in general, and a **php wordpress plugin**. They both do the same thing, they only differ in how they are configured and in the way they interact with the database. So the best solution was creating a **common php core repo**, that is included by the backend and the plugin as a **git submodule**.

## How was it made

Because both the backend and the plugin use **Rector** to downgrade the php code to **legacy php code versions**, ideally, the **last version of php** can be used for this code. At the time this text was written, the latest version was **php 8**.

For the dependencies, **composer** has been used, because it is almost unique way to write good php code and integrate libraries.

## How to use it

To use the core php code:
1. add it as a git submodule
2. execute `composer install` to install the dependencies
3. include in your code the file `autoload.php` in order to add the namespaces exported by this module

## What does the core submodule provide

It provides first of all some **types**:
* `DriverType` which is actually an enum of the possible drivers
* `Image` class, used internally
* `InputImage` class, used internally

It provides also the **drivers**, used to parse the different types of cameras' directories structure:
* `BaseDriver` the abstract class that all the real drivers extend
* all the other drivers

It provides all the **utilities** that do almost everything that is needed to be done by webfoto backends:
* `EmailService` which is the class used to send emails
* `FtpService` which is the class used to upload ftp files
* `Logger` which is a useful logger that saves to a `webfoto.log` file and allows a easier debug of php
* `BaseDatabaseService` which is an abstract class that the code using this core module will extend to implement its own way to interact with the db
* `ImagesHandler` which uses most of the other core code and does almost everything at a higher level
