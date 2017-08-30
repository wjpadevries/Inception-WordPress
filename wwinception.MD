# WWInception

The wwinception.php file is a way to receive published articles from WoodWing Inception or Aurora. in this documentation where you read Inception you can also read Aurora.


## Prerequisites
The wordpress module 'iframe' is required when you want to show the Inception articles as-is.

Download from : ``https://wordpress.org/plugins/iframe/ ``


## Installation 
Place the wwinception.php in the root of the WordPress folder.

## Configuration
The configuration needs to be done in the file ``wwinception.conf.php``

the settings to check are:

	- location of temp-folder
	- location of logfiles
	- (optional) URL to SubServer
	

	
##### temp folder location	

the temp file location is specified in the TEMPDIR 
``define( 'TEMPDIR'  , '/temp/inception/');``

please make sure this folder is Read/Write for the webservice/PHP process

##### Log folder location
the log folder location is specified in the LOGPATH 

``define( 'LOGPATH'  , dirname(__FILE__) . '/wwlog/'); ``

##### (optional) URL to SubServer
If your wordpress server is not reachable from the outside world then the Inception publish process can not push the article-packet to your WP.
To work around this a 2-step process can be setup using a SubServer.
In this case the SubServer is running in the cloud and will receive the published articles. 
Then your local WP can 'pull' the published articles from this SubServer.

To make this work, the URL to the SubServer needs to be specified.

To 'pull' the content, the wwinception.php needs to be called from crontab on a regular interval.

## Run Config test
The configuration can be tested by invoking the script with the 'testconfig' parameter
``http://<server>/wordpress/wwinception.php?testconfig=1``



## Who is in Control? Choosing the Display mode

When publishing Inception articles to a webCMS you have a decision to make.

The idea of Inception is that the writer has to full control about how the published article will look. But many webCMS systems are much more structured and will have custom 'widgets' to display for example images as slideshow or to embed video. So there is both a mismatch in concept (writer has design control versus webCMS has design control) and a mismatch in structure (writer decides on used widgets/components versus webCMS determines possible widgets/components)

with other words, you have to determine who should be in control, the writer or the webCMS?

### Writer in Control

In case you put the writer in control of the published article, then we will display the article in WordPress as it was designed in Inception. This is done by using the iframe plugin. The complete Inception article will be rendered in the iframe, using all styling, widgets and javascript from Inception.

This is a solution that will give a predictable result 'out-of-the-box'

The disadvantage of the iframe method is that the article is shown as designed in Inception and the used styling can be totally different then the active WordPress thema.

For real live implementations it is offcourse possible to create an inception styling that mimics the look and feel of the WebCMS. Allthough this also can be seen as branding.

### WebCMS in Control
In case you want to put the webCMS in control of the look and feel of the published articles then you will need to solve several technical challenges.

- mapping article components to cms-content structure
- mapping inception widgets to cms widgets
- uploading images to the correct WP structure
- uploading videos to the correct WP structure
- translate element/component names to WP-styling names

Based on the above (possible incomplete) list you can understand that this solution will not be available out-of-the-box. This solution will always be a fine-tuned connection between the possibilities of the CMS, custom styling in Inception and also some PHP coding to make mapping/conversions.


## Create the Custom channel in Inception



