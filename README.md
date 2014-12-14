P3T extractor
======

This is a PS3 Theme (.p3t) extractor and is currently used on http://ps3theme.net/.

Usage
==

Using this is quite simple.

 1. Put the file in your lib folder and include Autolosader.php:
 
    ```php
    include 'vendor/autoload.php';
    ```
 2. Create a `new Extractor()` object: 
 
    ```php
    $p3tfile = 'test.p3t';
    $extract_location = 'extracted/';

    $p3t = new P3TExtractor\Extractor($p3tfile, $extract_location);
    $p3t->parse();

    // Accepts one argument as to what to extract.
    // This method will return the things the P3T file supports, e.g. audio and/or dynamic
    $support = $p3t->dump_files();
    ```
 3. Please view the source for full explanation of the classes
