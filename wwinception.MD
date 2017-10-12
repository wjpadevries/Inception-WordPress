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

#### wordpress in the 'cloud'

The steps described here are intended for the situation where your wordpress installation is on the internet. Meaning NOT local, behind a firewall etc.

Assumed is that you have installed the wwinception.php, checked the config and run the config check.

- login to inception
- on th top-right, click on the figure icon,
- in the select list, choose 'configuration'
- the Channels admin page will appear
- choose 'custom channels'
- press 'Add Channel'

you will now have to fill in some properties for the custom channel:

- name: 'Wordpress-Iframe'
- target-Name: 'Wordpress'
- endpoint URL : <your-server>/wordpress/wwinception.php?iframe=1
- maximum imagesize can be left empty

It is important to notice that after a little while there should be a green 'Check' behind the URL, this indicates that the AWS-SNS handshake was performed and the channel should be able to receive articles

The channel we added will display the published articles in an iframe in wordpress, this will be 100% the look and feel as in the inception editor.

##### non-iframe wordpress

now add a second channel, target-name: 'Wordpress', name: 'Wordpress-native', endpoint URL : <your-server>/wordpress/wwinception.php

again, the custom channel will only work when the green-check is there


### If the green check does not appear
In case the green-check dos not appear behind the URL then there is a problem with the AWS-SNS 'handshake'

This handshake works as follows:

when the custom channel is defined and the endpoint URL is entered 
the inception server will try to create an AWS-SNS channel.

To validate this channel a specific JSON package is send to the specified URL. In the JSON package there is a 'callback' URL speficied.

The specified endpoint (our wwinception.php) will intercept the handshake package, extract the callback URL and will then (try-to) call this URL to confirm the endpoint.

If it is not possible from your wordpress server to perform a call to the web, then this handshake will fail. So make sure your server is allowed to make calls to other servers/services.

the server that will be called is most likely: https://sns.eu-west-1.amazonaws.com/

this is the [SubscribeURL] in the received 'SubscriptionConfirmation' JSON package.

the logfile written by the wwinception.php should show more information when this fails.


### Testing
- Create or open an article
- press the 'Publish' button
- select 'WordPress' as Target
- select 'Wordpress-native' or 'Wordpress-iframe' in publish-to
- press the 'Publish' button

In a little while the published article should apear on your wordpress installation.

 




