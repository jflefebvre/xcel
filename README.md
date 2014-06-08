xcel
====

A simple data import script from excel copy and paste. Inspired by a MailChimp feature.

### Install 

*Config*

Copy config.inc.default.php to config.inc.php
Change the values in the config file to match your mysql settings.
$config['table'] contains the name of the table for the import.

Install uglifyjs and uglifycss node modules

    npm install uglifyjs -g
    npm install uglifycss -g

Give execute right to the minify script
    chmod +x minify.sh

Run minify.sh

The minified css/js will be used if $prod = true in the config file.

### How to use

Pretty straightforward.

In step 1 :

[[/images/doc_step1.jpg]]	
Copy and paste from excel several lines of data in the textarea.
Click Upload.

In step 2 :

[[/images/doc_step2.jpg]]
For each column, the select bow displays the fields existing for the table specified
in the $config['table'] : map your fields as wanted.
Click import.

The data are imported.
