<?php
/*	UNG - UNG's Not Gallery
 *	Copyright (C) 2002-2007 tekniklr
 *	Some Parts (C) Chris Haynie, Dan Teasdale
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
 *	USA
 *
 *
 *	This script will display images, with thumbnails automatically generated.
 *	It will browse the directory tree and automagically create it's own
 *	hierarchy based on that.
 *	It will check to see if a thumbnail image already exists, if not
 *	it will create one for each image and save it for later use.
 *	It optionally interfaces with a MySQL database so that each picture can
 *	have comments that appear with it on the display page.
 *
 *	NOTES:
 *	To allow the script to create thumbnails, any directory it will be
 *	writing in has to be either owned by or owned by the same group
 *	as the web user.  If that's not possible, it has to be set to
 *	(0777)
 *
 *	To set more information about subdirectories, add an optional
 *	'dirinfo.txt' file to that directory.
 *	Set up this file as follows:
 *			Line1: Directory Name 
 *			Line2: Date
 *			Line3: Description
 *	Without this file, 'Name' will default to the name of the directory in the
 *	file structure, 'date' and 'desc' will be omitted. 
 *
 *	To further customize the display, create an alt_disp function. This will
 *	override the default_disp function (so it's a good idea to at least
 *	loosely base alt_disp on default_disp).  To prevent this function from
 *	getting lost when updating UNG, it's a good idea to keep it in the 
 *	configuration file specified by the $config_file variable.
 *
 *
 *
 *	The code is pretty messy- I wrote this in one go back when I was still new
 *	to PHP and haven't really gone over it to any great (well, any) extent 
 *	since then.  Add to that the modifications submitted by others, and you're
 *	left with a jumble of code that works by virtue of sheer stubbornness.  
 *	Needless to say, The code is probably very redundant with tons of 
 *	extraneous things.	If it isn't, then forget I said that  :) 
 *
 *	This script was originally made just for my own webpage back when I was 
 *	still new to PHP... (hence the mess).
 *	I've tried to clean it up a bit, but it is still rather customized
 *	for my setup.  The most blatant example of this is all the CSS I left
 *	in there - in an effort to make lives easier, the option to use the builtin
 *	css is available.  To use it, set $builtin_css in the configuration section to
 *	true. To modify the built in css see the function builtin_css() {} at the end of
 *	the configuration section.	If you set $builtin_css to false, you can safely
 *	delete the builtin_css() function to help make the script size smaller if you wish.
 *
 *
 *
 *	Initial author:
 *		tekniklr
 *		tekniklr@tekniklr.com
 *		http://www.tekniklr.com
 *
 *	Modified by:
 *		Chris Haynie (Sax)
 *		chris@chrishaynie.com
 *		http://www.chrishaynie.com/
 *
 *		Dan Teasdale
 *		dteasdale@pandemicstudios.com.au
 *
 *
 *
 *
 *	CHANGES:
 *	- (11/19/2007 - tekniklr) Fixed a bug wherein the cached image directory was browseable.
 *	- (11/19/2007 - tekniklr) Fixed a bug in the way exif data was handled for some images.
 *	- (11/18/2007 - tekniklr) Added an 'emph' css class which initially functions identically to the 'inflate' class.  This is to allow links in one case to appear on dark backgrounds and links in the other to appear on light.
 *	- (10/28/2007 - tekniklr) Fixed a bug wherein the next/previous buttons on individual images didnt' respect the requested sort order.
 *	- (10/09/2007 - tekniklr) Added a configuration variable, $extra_sort_desc, to allow you to force files/directories that begin with a specified string (e.g., 'img_') to sort descending in date sort mode.  These items will appear below datestamped files, but above named files.
 *	- (10/09/2007 - tekniklr) Added a configuration variable, $file_sort_method, that works the same way as $sort_method to allow one to sort files and firerctories in different ways.
 *	- (8/29/2007 - tekniklr) Exif data in images is read in a more intelligent way.
 *	- (8/29/2007 - tekniklr) Fixed a bug wherein the check for banned IP addresses was being way too liberal.
 *	- (8/29/2007 - tekniklr) Made MySQL connections use a specific database handle to prevent possible confusion, and added some MySQL wrapper functions to facilitate error reporting.
 *	- (8/27/2007 - tekniklr) Fixed a bug wherein the config file was being included too late.
 *	- (8/23/2007 - tekniklr) Updated spam blocking behavior.  Added akismet support, and updated the format of notification emails.
 *	- (7/21/2007 - tekniklr) Fixed a bug wherein images weren't being sorted properly for the previous and next functions.
 *	- (3/22/2007 - tekniklr) Added a block_robots configuration variable which, if set to true, causes a session variable to be created which is later checked for before comments are added. This is to try and verify that comments are only being submitted in non-spammy-robot ways, and probably won't help matters, but shouldn't hurt.
 *	- (9/26/2006 - tekniklr) Increased verbosity in disallowed comment mailings- now the mail will contain the reason the post failed, as well as the post contents.
 *	- (9/26/2006 - tekniklr) Fixed the comment posting code, so that we have both security and legibility.  Adding the former before removed the latter.
 *	- (9/26/2006 - tekniklr) Fixed a missing semicolon in the SQL section, found by Darkbliss.
 *	- (9/7/2006 - tekniklr) Updated to better prevent MySQL injection attacks.  A side effect of this is that UNG will no longer work with versions of PHP less than 4.3.
 *	- (9/7/2006 - tekniklr) The banned words list will now also work against urls submitted with comments.
 *	- (5/29/2006 - tekniklr) Fixed a bug wherein it was possible to prevent email notification from being sent by closing a browser window.
 *	- (5/10/2006 - tekniklr) Added some spambot protection - the admin will get emailed when someone tries to (1) post a comment containing a word (e.g., 'blackjack') in a new banned words file, (2) tries to post a comment with newlines in the email headers, (3) tries to post a comment which contains a URL also entered into the comment_url field.
 *	- (2/15/2006 - tekniklr) Fixed a bug wherein the date would still be displayed on individual picture pages, even if $date_info was false.
 *	- (2/15/2006 - tekniklr) Added a $date_no_exif option to inhibit the date display if there is no exif data.
 *	- (11/27/2005 - tekniklr) Fixed a bug where an error message was bein printed if the EXIF data couldn't be read.
 *	- (10/30/2005 - tekniklr) Fixed a bug in parsing the banlist to weed out posting by banned users.
 *	- (8/16/2005 - Sax) Added option to enable gz compression to reduce page load times and reduce bandwidth.
 *	- (7/28/2005 - tekniklr) Fixed a security hole wherein email headers could be forged by including newlines in the name or email fields when posting a comment.
 *	- (5/2/2005 - tekniklr) Replaced all instances of $HTTP_SERVER_VARS with the more correct $_SERVER.
 *	- (4/29/2005 - tekniklr) Added image dimensions and date display to the individual image view.
 *	- (4/29/2005 - tekniklr) Added last modification date display under categories/subcategories.
 *	- (4/29/2005 - tekniklr) Added $sort_method configuration variable to change the default sorting order for subcategories
 *	- (3/24/2005 - tekniklr) We can get the dates from exif data now, too
 *	- (3/24/2005 - tekniklr) Added a feature to print dates (from filectime) under thumbnails
 *	- (12/17/2004 - tekniklr) Finally gave the project a real name: UNG (UNG's Not Gallery)
 *	- (11/2/2004 - Sax) Fixed bug where, if $comments_op was set to false by default, a mysql function was still being called causing servers without mysql support to produce error messages (line 1230)
 *	- (10/5/2004 - tekniklr) Fixed handling of opening full-sized images because Beth (http://loxosceles.org) was complaining.
 *	- (9/24/2004 - tekniklr) Fixed a bug wherein the defaul display was not valid XHTML (missing slash).
 *	- (9/24/2004 - tekniklr) Changed the categoryname style to look entirely different.
 *	- (9/24/2004 - tekniklr) Added a horizontal rule between category descriptions and category content, when descriptions appear.  Tweaked the display of this information.
 *	- (9/24/2004 - tekniklr) Added a horizontal rule between subdirectories and images when a directory contains both.
 *	- (9/24/2004 - tekniklr) Decreased margins around main display area.
 *	- (9/24/2004 - tekniklr) Tweaked the display of category info- condensing it a bit.
 *	- (9/24/2004 - tekniklr) Changed class of category names from 'inflate' fo 'categoryname', added this new class to the default stylesheet.  It is the same as 'inflate' by default, but things are more configurable now.
 *	- (9/24/2004 - tekniklr) Added a variable to configure the background color of category images.
 *	- (9/10/2004 - tekniklr) Implemented changes made  by Dan (dteasdale@pandemicstudios.com.au) to allow categories to have thumbnails,   and to cache  generated images.
 *	- (9/10/2004 - tekniklr) Made the configuration a helluva lot neater.
 *	- (9/10/2004 - tekniklr) Implemented a fix (and then some) sbmitted by Dan (dteasdale@pandemicstudios.com.au) making $comments_op actually work (really this time).
 *	- (9/10/2004 - tekniklr) Replaced all instances of $HTTP_GET_VARS and $HTTP_POST_VARS with the more correct $_GET and $_POST.
 *	- (8/31/2004 - tekniklr) Fixed a bug where mid images where displayed in the directory view.
 *	- (8/31/2004 - tekniklr) Removed file sizes from the directory display, since it is misleading now that there is an additional image size involved.
 *	- (8/31/2004 - tekniklr) Added a buffer around the image in the full-image popop window to hopefully ensure that no scrolling is needed.
 *	- (8/31/2004 - tekniklr) Added a middle-sized image for the comments pages so that large images don't need to be constangly downloaded.
 *	- (8/31/2004 - tekniklr) Added a copy_resize function so the code to generate thumbnails (and now mid images) is separate from any other functions.
 *	- (8/15/2004 - tekniklr) Changed the way the dirinfo file works, so the comment area can have linebreaks.  Repressed some pesky errors this  was causing.
 *	- (8/15/2004 - tekniklr) Modifications to the date_sort function,  making it more intuitive to use.
 *	- (8/15/2004 - tekniklr) Added a modification_sort function to sort directories by their last modified date.
 *	- (4/14/2004 - tekniklr) Redesigned the database to be a bit more sane; updated database creation instructions.
 *	- (4/14/2004 - tekniklr) Added branding to the default footer.
 *	- (12/24/2003 - Sax) Added Up one level links to bottom of thumbnail pages as well, to make navigation easier for long pages.
 *	- (12/18/2003 - tekniklr) Fixed a bug where $comments_op wasn't global in print_img, so comment totals weren't being printed in the directory display.
 *	- (12/08/2003 - Sax) added $linkdir option for the full url to the image directory.
 *	- (12/07/2003 - Sax) added $image_function option, for compatibility with older versions of GD.
 *	- (12/07/2003 - Sax) Made $comments_op actually NOT try to connect to a database IF you disable comments
 *	- (11/2/2003 - tekniklr) Made it so only one cookie is set instead of 3.
 *	- (11/2/2003 - tekniklr) Worked on a bug in the previous/next link display.
 *	- (11/2/2003 - tekniklr) Added previous/next links on individual image displays so that images may be quickly cycled through.
 *	- (10/22/2003 - tekniklr) Fixed a security bug allowing people to view arbitrary directories (e.g. cgi-bin) located in the docroot.
 *	- (10/8/2003 - tekniklr) stripslashes() from contents of emails sent, emails are now sent so that you can see the HTML.  This started as a bug, but I like it, so now it's a feature.
 *	- (10/8/2003 - tekniklr) Added an optional way to ban IPs/hostnames, with help from Sax.
 *	- (10/8/2003 - tekniklr) Fixed comment posting so that it will always be valid XHTML.  Fixed a security bug.
 *	- (9/29/2003 - tekniklr) Removed final vestiges of the HTML 4.0 button.
 *	- (9/28/2003 - tekniklr) Fixed one character that was breaking the XHTML 1.0 validness.
 *	- (9/26/2003 - tekniklr) Made everything valid XHTML 1.0, as a result, removed the 'Valid HTML 4.0' button.
 *	- (9/18/2003 - tekniklr) fixed some formatting bugs in the CSS
 *	- (9/06/2003 - Sax)removed $sort_alpha option and added customized sorting algorithm. Bugs to ensue :)
 *	- (9/03/2003 - tekniklr) Changed the way config_file works, so it will search the include path.
 *	- (9/03/2003 - tekniklr) Updated doc a bit.
 *	- (9/03/2003 - tekniklr) Added option to use alt_disp function instead of default_disp function
 *	- (9/03/2003 - Sax) cleaned up NOTES: section a bit.
 *	- (9/03/2003 - Sax) added $sort_alpha = true|false option to sort main and subdir listings either alphabetically or reverse alphabetically
 *	- (9/03/2003 - Sax) added -optional- external config file option.
 *	- (9/03/2003 - tekniklr) Fixed bug where two dashes were displayed in some places in some implementations.
 *	- (9/01/2003 - tekniklr) Made it so that the number of subdirectories are displayed.
 *	- (8/29/2003 - Sax) Made Gif and Png thumbnails create using truecolor.
 *	- (8/29/2003 - Sax) Modified CSS for .coment_notify and .thumbnail
 *	- (8/29/2003 - tekniklr) Fixed alt and title tags to no longer display "No comments" when comments are disabled for an image.
 *	- (8/29/2003 - tekniklr) Added comment_notify CSS so that the extra info displays that differently, when applicable.
 *	- (8/29/2003 - tekniklr) Tweaked view in the extra info display mode.
 *	- (8/29/2003 - tekniklr) Fixed IE bug.
 *	- (8/29/2003 - Sax) Added Bugs Section.
 *	- (8/29/2003 - Sax) made configuration section hella easier to read.
 *	- (8/29/2003 - Sax) fixed $gallery_title - wasn't displaying.
 *	- (8/29/2003 - Sax) added $extra_info option to display filesize/dimension info under thumbnails and added .thumbnail to css to accomodate the $extra_info mode
 *	- (8/28/2003 - tekniklr) Indented dates under descriptions in the category pages.
 *	- (8/28/2003 - tekniklr) Made $admin_mail global, so it actually works.
 *	- (8/28/2003 - tekniklr) Made it so category descriptions are listed on the category pages.
 *	- (8/28/2003 - tekniklr) Made $comments_op global in default_disp()
 *	- (8/28/2003 - tekniklr) Fixed CSS stuff.
 *	- (8/28/2003 - tekniklr) Made header/footer handling sane.
 *	- (8/28/2003 - tekniklr) Changed $home to $rootdir to avoid possible conflicts.
 *	- (8/28/2003 - tekniklr) Made the code more humanly readable.
 *	- (8/28/2003 - Sax) added option to enable or disable comment viewing, meaning either it needs or doesn't need a mysql database.
 *	- (8/28/2003 - Sax) fixed small bug where if $builtin_css=true and fp_img=false it didn't print the headers.
 *	- (8/28/2003 - Sax) added Gif and Png support for the thumbnails YAY!
 *	- (8/27/2003 - tekniklr) added a variable for setting the permissions.
 *	- (8/27/2003 - Sax) fixed some spelling errors in the comments and explanations.
 *	- (8/27/2003 - Sax) added $header_file and $footer_file for easier website integration.
 *	- (8/27/2003 - Sax) made W3 Valid when using builtin_css(); (if you use your own header you're on your own).
 *	- (8/27/2003 - Sax) changed chmod from 777 to 644 on the thumbnails.
 *	- (8/27/2003 - Sax) removed non-layout inline styles putting them in the builtin_css() function.
 *	- (8/27/2003 - Sax) made image display nicer.
 *	- (8/27/2003 - Sax) made default font in builtin css arial.
 *	- (8/27/2003 - Sax) added option to use builtin css -- very awesome.
 *	- (8/27/2003 - Sax) made configuration section easier to read.
 *	- (8/26/2003 - Sax) fixed apostrophe bug in comment posting.
 *	- (8/26/2003 - Sax) Added $thumbvar for customized thumbnail prefix.
 *	- (8/26/2003 - tekniklr) Various code fixes, going by what Sax needed to install it.
 *	- (8/26/2003 - tekniklr) Initial public release.
 *
 *	TODO:
 *	- Documentation!!!!
 *	- Clean up Code more.
 *	- Add total count for all images and for number of subdirectories within a directory.
 *	- Add ability to exclude directories.
 *	- limit $thumbnails_per_page type of thing.
 *	- idea: instead of "Up One Level" list the folders above like one > two > three > current as links too.
 *	 
 *
 *	BUGS:
 *	- Can't get useful EXIF data from some images.
 *
 *
 */

#############################################################
############################################################
# DATABASE SETUP:										   #
# (Run the following on the database you are going to use) #
############################################################
#############################################################
/*

	create table comments_elements (
		id mediumint not null auto_increment,
		name text not null,
		primary key (id)
	);

	create table comments_comments (
		id mediumint not null auto_increment,
		element_id mediumint not null,
		date datetime not null,
		name text,
		email text,
		url text,
		comment text not null,
		hostname text not null,
		notify int(1) not null,
		primary key (id)
	);

*/

#############################################################
#############################################################
#					 Begin Configuration					#
#############################################################
#############################################################

#############################################################
###################### GENERAL CONFIG #######################
#############################################################

# optional external configuration file that will override these settings.
#	this option is ignored if the file doesn't exist. If you plan to use
#	an alt_disp function, it might be a good idea to put it in this file.
	$config_file		= "includes/ungconfig.php";

# email address to receive notifications when comments are posted.
	$admin_mail			= "admin@server.com";

# show thumbs for directory categories
# implies $cachedir
	$categorythumbs		= false;
# what color to make the background of category thumbs
# (e.g. for vertically aligned images)
	$catthumbcolor		= "transparent";
# whether or not to display subitem (image, or failing that, directory)
# count in the  bottom right cornerof   category thumbs
	$categorycount		= false;
# what colow to display $categorycount in
	$catcountcolor		= "yellow";
# small  image (on the order of 30x30) that will appear  behind the
# $categirycount
	$catcountimg		= false;

# sort method for subcategories
# 'mod' for modification_sort (sort by last modification time)
# anything else will default to date_sort (sort by filename, with numbers/dates
# first in descending order and alphabetically after that)
	$sort_method        = "date";
# sort method for files
# the usage is the same as for $sort_method
	$file_sort_method   = "date";
# when using date_sort, filenames beginning with dates will be sorted 
# descending followed by everthing else sorted alphabetically.  if this is
# set, any file/directory beginning with this string will also be sorted 
# descending, and will appear below datestamped files and above standard file
# names.  this is useful, say, if your camera outputs images named as
# img_####.jpg, for instance, to have the newest images appear at or near the
# top.
	$extra_sort_desc = '';
	
# display comment number under each thumbnail
	$comment_info		= true;
# display image dimensions under each thumbnail
	$size_info			= false;
# display dates under each thumbnail (using filemtime) or (optionally)
# exif data, if available (see below)
	$date_info			= false;
# format for displayed dates using PHP's date() function
	$date_fmt			= "Y-m-d";

# whether or not to use exif data to get image dates
	$use_exif			= false;
# when used with $use_exif, if the exif data can't be found, use data from
# filemtime instead
	$date_no_exif		= false;
	
# Enable / Disable comments to images.
	$comments_op		= false;

# Do you want to display images on this page?.
	$fp_img				= false;

# Uncomment to have this appear at the top of pages as a link to
# the main pics page.
	//$gallery_title	= "Pics";

# use css provided available only if you're not using $header_file
# to prevent conflicts.
	$builtin_css		= true;

# Function to use when creating images. Default is "imagecreatetruecolor"
# however if you have an older version of GD and it's not seeming to
# create the thumbnails change it to "imagecreate"
	$image_function		= "imagecreatetruecolor";

# Permissions to give to generated images  if you are not the apache 
# user, or in that	group, leave as 0666, otherwise you will not be
# able to manually delete/move the images later.
	$perms				= 0666;

# enable compression to decrease page load times and reduce bandwidth usage.
	$gz                 = true;

# If set to true, when existing comments are show a session variable will
# be set; when the comment form is submitted that variable will be checked
# for, and if nonexistant, it is assumed an evil robot is sending comment
# spam, and the post will be summarily ignored.  This will probably do no 
# good in the long run, but it certainly won't hurt, either.
	$block_robots       = false;

# If this is set, it will be used as an akismet key to check for comment
# spam.  You will need to have the akismet.class.php file in your include_path
# http://www.miphp.net/blog/view/php4_akismet_class/
	$akismet_key        = false;

#############################################################
###################### DATABASE CONFIG ######################
#############################################################
	$dbhost		 = "";			# MySQL hostname.
	$dbuser		 = "";			# username to log into MySQL.
	$passwd		 = "";			# password to log into MySQL.
	$dbname		 = "";			# name of database.


#############################################################
#################### FILENAMES/PATHS ########################
#############################################################

# uncomment to use - needed only if the script doesn't reside
#	in the servers root directory. if you use it put the full
#	url to the path of the folder containing the script trailing
#	slash not needed
	//$linkdir		= "http://server.com/some/sub/folder";

# path to the http root directory.
	$rootdir		= "/path/to/public_html/";

# pics directory, MUST be in the document root
	$pics			= "pics/";

# directory to store cached dynamic data
# implied by $categorythumbs, default = $pics."cache"
	$cachedir		= $pics."cache";

# prefix to the thumbnails that will be generated.
	$thumbvar		= "thumb_";

# prefix to the medium-sized images that may be generated.
	$midvar			= "mid_";

# File with banned hostnames and/or IP addresses, one per line.
	$banfile		= "banlist.php";
# File with banned hostnames and/or IP addresses, one per line.
	$bannedwordfile	= "bannedwords.php";

# file to include at the top of all the pages - uncomment to use.
	//$header_file	= "header.php";

# file to include at the bottom of all the pages - uncomment to use.
	//$footer_file	= "footer.php";


#############################################################
#################### FEATURED IMAGES ########################
#############################################################

# array containing images to appear on the main page.
	$pics_me = array(
			array(
				"img"=>"image.jpg",
				"path"=>"$pics",
				"alt"=>"Heading",
				"date"=>"August 2003",
				"desc"=>"This description will appear besides this pic."
				)
			);


#############################################################
#############################################################
#					 End Configuration						#
#  Don't edit below here unless you know what you're doing! #
#############################################################
#############################################################

$pics_version = "20071119.1";

@include($config_file);

# start a session, for super-weak spam comment protection
# (see 'block_robots', above)
if ($block_robots) {
	session_start();
}

if ($gz) {
	ob_start("ob_gzhandler");
}

if (isset($extra_info) && !isset($comment_info)) {
	$comment_info = $extra_info;
}

if ($categorythumbs && !$cachedir) {
	$cachedir = $pics."cache";
}

function builtin_css() {
	global $gallery_title, $builtin_css;
	if ($builtin_css) {
		echo <<<EOF
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd ">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
			<head>
			<title>$gallery_title</title>
			<meta http-equiv="Content-Type" content="text/html; charset=us-ascii" />
			<style type="text/css">
				body {
					font-family: sans-serif;
				}
				div.h1 {
					text-align: center;
					color: #4F4E99;
					font-size: xx-large;
				}
				img.art {
					margin: 3px;
					border: 2px solid; 
					-moz-border-radius: 5px
				}
				.thumbnail {
					font-size: x-small;
					text-align: center;
					float: left;
					color: #777777;
				}
				A:link.art, A:visited.art {
					color: #BBBBBB
				}
				A:hover.art {
					color: #DDDDDD
				}
				.hr {
					vertical-align: bottom;
					margin-top: 15px
					;margin-bottom: 10px;
					color: #4F4E99;
					padding: 4px;
					background-color: #eff0f4;
					border-top: 3px solid #4F4E99;
					border-bottom: 3px solid #4F4E99;
				}
				.pagetitle {
					margin-left:30px;
					color: #4F4E99;
					font-weight: bold;
					font-size: x-large;
					text-decoration:none;
				}
				.leftside {
					float: left;
					margin: 0px 0px 0px 5px;
					font-size: large;
				}
				.rightside {
					float: right;
					text-align: right;
					margin: 3px 15px 0px 0px;
					font-size: medium;
					font-weight: normal;
				}
				.date {
					color: #00cc00
				}
				A:link.categoryname, A:visited.categoryname {
					font-weight: bold;
					-moz-border-radius:	5px;
					color:	#000000;
					background-color:	#DDDDDD;
					text-decoration: none;
					padding: 3px;
					border: 1px solid #000000;
				}
				A:hover.categoryname	{
					font-weight: bold;
					-moz-border-radius:	5px;
					color:	#DDDDDD;
					background-color:	#333333;
					text-decoration: none;
					padding: 3px;
					border: 1px solid #DDDDDD;
  				}
				A:link.inflate, A:visited.inflate {
					color: #0000FF;
					text-decoration: none;
				}
				A:hover.inflate	{
					color: #0000FF;
					text-decoration: none;
					font-weight: bold;
				}
				A:link.emph, A:visited.emph {
					color: #0000FF;
					text-decoration: none;
				}
				A:hover.emph	{
					color: #0000FF;
					text-decoration: none;
					font-weight: bold;
				}
				.comment_border {
					-moz-border-radius: 5px;
					border: 2px solid #4F4E99;
					margin: 10px;
				}
				.comment_header {
					background : url(null) fixed no-repeat;
					border-bottom: 1px solid #4F4E99;
					margin-left: 10px;
					margin-right: 10px;
				}
				.comment_input {
					margin:			0px 0px 5px 0px;
					padding:		2px;
					width:			200px;
					font-family:		Tahoma, Lucida, Arial, Helvetica, Sans-Serif;
					font-size:		small;
					border:			1px solid #808BB7;
					-moz-border-radius:	5px;
					background-color:	#FFFFFF;
					color:			#666666;
  				}
  				.comment_input[type=submit] {
					margin:			0px 0px 5px 0px;
					padding:		2px;
					font-family:		Tahoma, Lucida, Arial, Helvetica, Sans-Serif;
					font-size:		small;
					font-weight:		bold;
					background-color:	#808BB7;
					color:			#EEEEEE
				}
				.comment_text {
					margin:			0px 0px 5px 0px;
					padding:		2px;
					width:			100%;
					height:			75px;
					font-family:		Tahoma, Lucida, Arial, Helvetica, Sans-Serif;
					font-size:		small;
					word-wrap:		break-word;
					border:			1px solid #808BB7;
					-moz-border-radius:	5px;
					background-color:	#FFFFFF;
					color:			#7F7D73;
				}
				.comment_notify {
					font-weight: bold;
					color: #FF8C8C;
				}
			</style>
			</head>
			<body>
EOF;
	}
}

// banned addresses
$banned = array();
$banlist = @file($banfile, 1);
if (is_array($banlist)) {
	foreach($banlist as $line) {
		$trimmed = trim($line);
		if (!empty($trimmed) && (strpos($trimmed, '?') === false)) {
			$banned[] = $trimmed;
		}
	}
}

// banned words
$banned_words = array();
# turns file of banned words to an array
# (for ease of processing)
$banlist = @file($bannedwordfile, 1);
if (is_array($banlist)) {
	foreach($banlist as $line) {
		$trimmed = trim($line);
		if (!empty($trimmed) && (strpos($trimmed, '?') === false)) {
			$banned_words[] = $trimmed;
		}
	}
}

$exts_jpeg = array();
$exts_png = array();
$exts_gif = array();
if (function_exists("imagepng")) {
	$exts_png[]="png";
	$exts_png[]="PNG";
}
if (function_exists("imagejpeg")) {
	$exts_jpeg[]="jpg";  
	$exts_jpeg[]="JPG";  
	$exts_jpeg[]="jpeg";  
	$exts_jpeg[]="JPEG";  
}
if (function_exists("imagegif"))
{
	$exts_gif[]="gif";
	$exts_gif[]="GIF";
}
$exts = array_merge($exts_jpeg, $exts_png, $exts_gif);

function print_footer() {
	global $pics_version;
	echo <<<EOF
		<br clear="all" />
		<div style="text-align: center; font-size: smaller; font-weight: bold;">
		Powered by <a href="http://tekniklr.com/programs.php#UNG">UNG $pics_version</a>
		</div>
		</body>
		</html>
EOF;
}

// these cookies store a user's info so they don't have to enter 
// it each time they make a comment- it doesn't do anything skeevy	:)
$expiry = mktime(0, 0, 0, 1, 1, 2037);	// expires January 1, 2037
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$commentinfo = array($_POST['name'], $_POST['email'], $_POST['url']);
	setcookie('commentinfo', implode("|**|", $commentinfo), $expiry, '/');
}


// return sorted array in order 3 2 1 A B C
// use like:
//	$level_one = list_sdir($rootdir.$pics);
//	$h = date_sort($level_one);
//	foreach ($h as $i) {
//		print_dir_name($i);
//	}
function date_sort($dirname) {
	global $extra_sort_desc;
	$dates=array();
	$others=array();
	$extra=array();
	$result=array();

	foreach($dirname as $entry) {
		$entry_brief=basename(strval($entry));
		if(is_numeric(substr($entry_brief,0,4))) {
			$dates[$entry]=$entry_brief;
		}
		elseif (!empty($extra_sort_desc) && (substr($entry_brief,0,strlen($extra_sort_desc)) == $extra_sort_desc)) {
			$extra[$entry]=$entry_brief;
		}
		else {
			$others[$entry]=$entry_brief;
		}
	}
	if($dates) {
		arsort($dates);
	}
	if ($extra) {
		arsort($extra);
	}
	if($others) {
		natcasesort($others);
	}

	$result_array=array_merge($dates,$extra);
	$result_array=array_merge($result_array,$others);
	foreach($result_array as $entry => $entry_brief) {
		$result[]=$entry;
	}

	return $result;
}

// return sorted array in order of last modification time
// use like:
//	$level_one = list_sdir($rootdir.$pics);
//	$h = modification_sort($level_one);
//	foreach($h as $i) {
//		print_dir_name($i);
//	}
function modification_sort($dirname) {
	$out = $dirname;
	usort($out, "modification_compare");
	return $out;
}

function modification_compare($a, $b) {
	$amod = filemtime($a);
	$bmod = filemtime($b);
	if ($amod == $bmod) {
		return 0;
	}
	return ($amod > $bmod) ? -1 : 1;
}

# instead of posting a comment, email the administrator that someone is
# trying something bad
function disallow_post($comment_name, $comment_email, $comment_url, $host, $ip, $cmt_date,  $item, $comment, $reason = false) {
	global $admin_mail;
	$out = "
From:            $comment_name <$comment_email>
Url:             $comment_url
Hostname:        $host <$ip>
Time submitted:  $cmt_date
Comment made to: $item
Comment:
$comment";
	if (!$reason) {
		$reason = "Someone's doing something nasty with comments!";
	}
	mail($admin_mail, "Disallowed post: $reason", "$reason\n\nHere is what they TRIED to send:\n\n\n---------------------------------------\n\n\n$out");
	return false;
}

// return array of subdirectories of given directory
function list_sdir($dirname) {
	global $pics, $cachedir;
	if($dirname[strlen($dirname)-1]!='/') {
		$dirname.='/';
	}
	$result_array=array();
	$handle = opendir($dirname);
	while (false !== ($file = readdir($handle))) {
		if($file=='.'||$file=='..'||$file==$cachedir||$file==substr($cachedir, strlen($pics))) {
			continue;
		}
		if(is_dir($dirname.$file)) {
			$result_array[]=$dirname.$file;
		}
	}
	closedir($handle);
	return $result_array;
}

// return array of valid files in given directory
function list_files($dirname,$showgen = false) {
	global $pics, $exts, $thumbvar, $midvar, $cachedir;
	if($dirname[strlen($dirname)-1]!='/') {
		$dirname.='/';
	}
	$result_array=array();
	$handle = opendir($dirname);
	while (false !== ($file = readdir($handle))) {
		if($file=='.'||$file=='..'||$file==$cachedir||$file==substr($cachedir, strlen($pics))) {
			continue;
		}
		if(is_dir($dirname.$file)) {
			continue;
		}
		if (substr($file, 0, strlen($thumbvar))===$thumbvar && !$showgen) {
			continue;
		}
		if (substr($file, 0, strlen($midvar))===$midvar && !$showgen) {
			continue;
		}
		else {
			$place = strpos($file, '.');
			$ext = substr($file, $place+1);
			if (in_array($ext, $exts)){
				$result_array[]=$dirname.$file;
			}
		}
	}
	closedir($handle);
	return $result_array;
}

// find a thumb for the current dir
function get_dir_image($dirname, $md5sum) {
	global $rootdir, $cachedir, $thumbvar;
	$files = list_files($rootdir . $dirname,true);
	foreach ($files as $key => $value) {
		if (strpos($value,$thumbvar)) {
			copy($value,getcwd() . "/" .$cachedir . "/" . $md5sum . ".jpg");
			return true;
		}
	}
	$sdirs = list_sdir($rootdir . $dirname);
	foreach ($sdirs as $skey => $svalue) {
		$val = get_dir_image(substr($svalue,strlen($rootdir)), $md5sum);
		if($val) {
			return true;
		}
	}
	return false;
}

// given a path, print info for that directory
function print_dir_name($dirname) {
	global $rootdir, $categorythumbs, $cachedir, $catcountcolor, $categorycount, $catcountimg, $catthumbcolor, $date_fmt;
	$info = @file($dirname."/dirinfo.txt");
	$name = trim(@array_shift($info));
	$date = @array_shift($info);
	$desc = @join(" ", $info);
	if (empty($name)) {
		$pos = strrpos($dirname, "/");
		$name = substr($dirname, $pos+1);
	}
	if (strlen($date)>3) {
		$date = "( $date )";
	}
	if (strlen($desc)>3) {
		$desc = "$desc";
	}
	$snip = strlen($rootdir);
	$link = substr($dirname, $snip);
	$num = sizeof(list_files($dirname));
	$count = $num;
	if ($num==1) {
		$num = "<em>one image</em>";
	}
	else if ($num>0) {
		$num = "<em>$num images</em>";
	}
	else {
		$num = "";
	}
	$dmod = date($date_fmt, filemtime($dirname));
	$dnum = sizeof(list_sdir($dirname));
	if ($count == 0) {
		$count = $dnum;
	}
	if ($dnum==1) {
		$dnum = "<em>one subcategory</em>";
	}
	else if ($dnum>0) {
		$dnum = "<em>$dnum subcategories</em>";
	}
	else {
		$dnum = "";
	}
	print "<div style=\"margin: 15px  5px  15px 5px\">";
	if ($categorythumbs) {
		// figure out the sum
		$md5sum = md5($link);
		// if the thumb doesn't exist, find one
		if(!is_file(getcwd() . "/" . $cachedir . "/" . $md5sum . ".jpg")) {
			get_dir_image($link,$md5sum);
		}
		if ($categorycount) {
			$countdisp = $count;
		}
		print "<table onclick=\"window.self.location='$PHP_SELF?dir=$link'\" style=\"background: url(/$cachedir/$md5sum.jpg) no-repeat $catthumcolor;\"><tr><td width=\"90\" height=\"50\"></td></tr><tr><td></td><td width=\"29\" height=\"30\"";
		if ($catcountimg) {
			print " background=\"$catcountimg\"";
		}
		print " valign=\"middle\" align=\"center\" style=\"color: $catcountcolor\">$countdisp</td></tr></table>";
	}
	print "\n<div style=\"white-space: nowrap; padding: 5px 0px 5px 0px;\"><a class=\"categoryname\" href=\"$PHP_SELF?dir=$link\">$name</a> <em class=\"date\">$date</em> ";
	if ((!empty($num))||(!empty($dnum))) {
		print " - $num";
	}
	if (!empty($dnum)) {
		if (!empty($num)) {
			print ", ";
		}
		print $dnum;
	}
	print "\n</div><span class=\"date\">(Last updated: $dmod)</span><br />";
	print "\n$desc</div>\n\n";
}

// create a resized image (e.g. a thumbnail)
function copy_resize($oldpath, $oldsize, $newwidth, $newheight, $newpath) {
	global $rootdir, $pics, $PHP_SELF, $thumbvar, $perms, $image_function, $linkdir;
	if ($oldsize[2] == 1) {
		$oldimg = imagecreatefromgif("$oldpath");
		$newimg = $image_function($newwidth,$newheight);
		imagecopyresized($newimg,$oldimg,0,0,0,0,$newwidth,$newheight,$oldsize[0],$oldsize[1]);
		imagegif($newimg,$newpath);
		imagedestroy($newimg);
		chmod($newpath,$perms);
	}
	elseif ($oldsize[2] == 2) {
		$oldimg = imagecreatefromjpeg("$oldpath");
		$newimg = $image_function($newwidth,$newheight);
		imagecopyresized($newimg,$oldimg,0,0,0,0,$newwidth,$newheight,$oldsize[0],$oldsize[1]);
		imagejpeg($newimg,$newpath);
		imagedestroy($newimg);
		chmod($newpath,$perms);
	}
	elseif ($oldsize[2] == 3) {
		$oldimg = imagecreatefrompng("$oldpath");
		$newimg = $image_function($newwidth,$newheight);
		imagecopyresized($newimg,$oldimg,0,0,0,0,$newwidth,$newheight,$oldsize[0],$oldsize[1]);
		imagepng($newimg,$newpath);
		imagedestroy($newimg);
		chmod($newpath,$perms);
	}
	else { 
		echo "Image isn't a valid file-format or is corrupt or something.";
	}
}

// display images, making thumbnails if necessary
// $desc is either an empty string or a description that will appear
// next to the thumbnail
// $comments is a boolean telling you whether comments are allowed on
// this image or not
// These variables are useful in case you want to display more images
// from the default_disp() function
function print_img($img, $desc, $comments) {
	global $rootdir, $pics, $PHP_SELF, $thumbvar, $perms, $comment_info, $size_info, $date_info, $date_fmt, $use_exif, $date_no_exif, $image_function, $linkdir, $comments_op, $dbh;
	$snip = strlen($rootdir);
	$pos = strrpos($img, '/');
	$fname = substr($img, $pos+1);
	$imgpath = substr($img, $snip, -strlen($fname));
	$thumbname = $thumbvar.$fname;
	if (!file_exists($rootdir.$imgpath.$thumbname)) {
		$oldsize = getimagesize($rootdir.$imgpath.$fname);
		usleep(1500);
		$newheight = 100;
		$ratio = $oldsize[1]/$newheight;
		$newwidth = $oldsize[0]/$ratio;
		copy_resize($rootdir.$imgpath.$fname, $oldsize, $newwidth, $newheight, $rootdir.$imgpath.$thumbname);
	}
	$thumbsize = getimagesize($rootdir.$imgpath.$thumbname);

	# do we want to try to use exif data?
	if ($use_exif) {
		$img_exif = exif_read_data($rootdir.$imgpath.$fname, 'IFD0');
		$img_timestamp = strtotime($img_exif['DateTime']);
		$img_date = date($date_fmt, $img_timestamp);
	}
	# if we couldn't retrieve exif data, or do not wish to display it, it 
	# won't be displayed
	$show_exif = $use_exif;
	if ($show_exif && (!$img_exif || !$img_timestamp)) {
		$show_exif = false;
	}
	# if either the exif data failed, or we just don't want to use it,
	# set the date using the filemtime
	if ($show_exif === false) {
		$img_date = date($date_fmt, filemtime($rootdir.$input));
	}
	# if we've chosen not to display dates unless they come out of exif data
	if ($use_exif && $show_exif === false && !$date_no_exif) {
		$img_date = "";
	}

	$numcomments = num_comments($imgpath.$fname);
	$image_dimensions = getimagesize($rootdir.$imgpath.$fname);
	if ($numcomments==0) {
		$commentmsg = "No comments";
	}
	else if ($numcomments==1) {
		$commentmsg = "One comment";
	}
	else {
		$commentmsg = "$numcomments comments";
	}
	if (empty($desc)) {
		print "<div class=\"thumbnail\">";

		if ($comments){
			print "<a class=\"art\" href=\"$PHP_SELF?pic=$imgpath$fname\">";
		}
		else {
			print "<a class=\"art\" href=\"$linkdir/$imgpath$fname\">";
		}
		print "<img class=\"art\" src=\"$linkdir/$imgpath$thumbname\" border=\"0\" " . $thumbsize[3];
		if (($numcomments>0)||($comments)) {
			print " alt=\"$fname - $commentmsg\" title=\"$fname - $commentmsg\"";
		}
		else {
			print " alt=\"$fname\" title=\"$fname\"";
		}
		print " /></a>\n";
		print "<br />\n";

		# print various things under the thumbnail, if applicable.
		if ($comment_info) {
			if ($comments_op) {
				if ($numcomments>0) {
					print "<span class=\"comment_notify\">$commentmsg</span><br />";
				}
				else {
					print "$commentmsg<br />";
				}
			}
		}
		if ($date_info) {
			print "$img_date<br />";
		}
		if ($size_info) {
			print "$image_dimensions[0] x $image_dimensions[1]<br />";
		}

		print "</div>";
	}
	else {
		print "<tr><td valign=\"middle\" align=\"left\">";
		if ($comments){
			print "<a class=\"art\" href=\"$PHP_SELF?pic=$imgpath$fname\">";
		}
		else {
			print "<a class=\"art\" href=\"/$imgpath$fname\">";
		}
		print "<img class=\"art\" src=\"$linkdir/$imgpath$thumbname\" border=\"0\" " . $thumbsize[3];
		if (($numcomments>0)||($comments)) {
			print " alt=\"$fname - $commentmsg\" title=\"$fname - $commentmsg\"";
		}
		else {
			print " alt=\"$fname\" title=\"$fname\"";
		}
		print " /></a></td>\n";
		print "<td valign=\"top\" align=\"left\">$desc</td></tr>\n";
	}
}

// print pic
function print_pic($input) {
	global $rootdir, $PHP_SELF, $thumbvar, $midvar, $header_file, $footer_file, $builtin_css, $admin_mail, $banned, $banned_words, $linkdir, $comments_op, $dbh, $date_info, $date_fmt, $use_exif, $date_no_exifm, $block_robots, $file_sort_method, $akismet_key;
	$input = mysql_real_escape_string($input);
	$maxwidth = 600;
	$oldsize = getimagesize($rootdir.$input);
	if ($oldsize[0]>$maxwidth) {
		$width = $maxwidth;
		$ratio = $oldsize[0]/$maxwidth;
		$height = $oldsize[1]/$ratio;
# create/use middle-sized image
		$snip = strlen($rootdir);
		$pos = strrpos($input, '/');
		$fname = substr($input, $pos+1);
		$imgpath = substr($input, 0, $pos) . "/";
		$midview = $midvar.$fname;
		if (!file_exists($rootdir.$imgpath.$midview)) {
			usleep(1500);
			copy_resize($rootdir.$input, $oldsize, $width, $height, $rootdir.$imgpath.$midview);
		}
		$picview = $imgpath.$midview;
	}
	else {
		$width = $oldsize[0];
		$height = $oldsize[1];
		$picview = $input;
	}
	$pos = strrpos($input, "/");
	$link = substr($input, 0, $pos);
	$others = list_files($rootdir.$link);
	if ($file_sort_method == "mod") {
		$others = modification_sort($others);
	}
	else {
		$others = date_sort($others);
	}
	foreach ($others as $other) {
		$other = substr($other, strlen($rootdir));
		if (!isset($last)) {
			$first = $other;
		}
		if ($now) {
			$next = $other;
			$now = false;
		}
		if ($other == $input) {
			$prev = $last;
			$now = true;
		}
		$last = $other;
	}
	if (!isset($prev)){
		$prev = $last;
	}
	if (!isset($next)){
		$next = $first;
	}
	print "<hr align=\"center\" size=\"2\" width=\"95%\" noshade=\"noshade\" />";
	print "<center><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\"><tr><td align=\"left\" width=\"33%\">
		<a class=\"emph\" href=\"$PHP_SELF?pic=$prev\">&lt;-- previous</a>
		</td>
		<td align=\"center\" width=\"34%\">
		<a class=\"emph\" href=\"$PHP_SELF?dir=$link\">back</a></td>
		<td align=\"right\" width=\"33%\">
		<a class=\"emph\" href=\"$PHP_SELF?pic=$next\">next --&gt;</a>
		</td></tr></table></center>\n";
	if ($extra_info) { print "<div class=\"h1\">".basename($input)."</div>"; }
	print "<p align=\"center\">
		<a href=\"$linkdir/$input\" onclick=\"javascript: window.open('$linkdir/$input', 'blank', 'toolbar=no,width=";
	$jswidth = $oldsize[0]+20;
	print "$jswidth,height=";
	$jsheight = $oldsize[1]+20;
	print "$jsheight'); return false;\"><img src=\"$linkdir/$picview\" border=\"0\" width=\"$width\" height=\"$height\" alt=\"$input\" />";
	if ($input !== $picview) {
		print "<br /><span style=\"font-size: smaller\">Full-sized image (".$oldsize[0]."x".$oldsize[1].", opens in a new window)</span>";
	}
	print "</a>
		</p>\n";

	# print date of image, using exif date if available and desired, otherwise
	# using filemtime
	if ($use_exif) {
		$img_exif = exif_read_data($rootdir.$input, 'IFD0');
		$img_timestamp = strtotime($img_exif['DateTime']);
		$img_date = date($date_fmt, $img_timestamp);
	}
	# if we couldn't retrieve exif data, or do not wish to display it, it 
	# won't be displayed
	$show_exif = $use_exif;
	if ($show_exif && (!$img_exif || !$img_timestamp)) {
		$show_exif = false;
	}
	# if either the exif data failed, or we just don't want to use it,
	# set the date using the filemtime
	if ($show_exif === false) {
		$img_date = date($date_fmt, filemtime($rootdir.$input));
	}
	# if we've chosen not to display dates unless they come out of exif data
	if ($use_exif && $show_exif === false && !$date_no_exif) {
		$img_date = "";
	}
	if ($date_info && !empty($img_date)) {
		print "<p style=\"text-align: center\"><span class=\"date\">$img_date</span></p>\n";
	}

	if ($comments_op) {
		if ($block_robots) {
			# set a session variable, if when posting later this variable isn't
			# set, we assume a bot is doing the submitting
			$_SESSION['from_web'] = true;
		}
		
		// print comments that are already there
		$notify_email = array();
		$results = do_query("SELECT comments_comments.* FROM comments_comments, comments_elements where comments_elements.name='$input' and comments_comments.element_id=comments_elements.id order by comments_comments.date");
		$fields = @mysql_num_fields($results);
		$rows = @mysql_num_rows($results);
		if (!empty($rows)) {
			$j=0;
			$x=1;
			while($row=mysql_fetch_array($results)){
				for($j=0; $j<$fields; $j++){
					$name = mysql_field_name($results, $j);
					$object[$x][$name]=$row[$name];
				}
				$x++;
			}
			// print contents of table
			$ii=count($object);		   //quick access function
			for($i=1;$i<=$ii;$i++){
				$c_date = $object[$i]['date'];
				$datetime = mktime(substr($c_date, -8, 2), substr($c_date, -5, 2), substr($c_date, -2, 2), substr($c_date, 5, 2), substr($c_date, 8, 2), substr($c_date, 0, 4));
				$c_name = $object[$i]['name'];
				$c_email = $object[$i]['email'];	
				$c_url = $object[$i]['url'];
				$c_comment = $object[$i]['comment'];
				$c_notify = $object[$i]['notify'];
				if ($c_notify==1) {
					$notify_email[] = $c_email;
				}
				print "<div class=\"comment_border\">\n<div class=\"comment_header\">";
				print "<p style=\"float: left; margin: 5px 10px 5px 10px;\">";
				if (!empty($c_email)) {
					print "<a href=\"mailto:$c_email\">";
					if (!empty($c_name)) {
						print "<strong>$c_name</strong>";
					}
					else {
						print "$c_email</a>";
					}
					print "</a>";
				}
				else if (!empty($c_name)) {
					print "<strong>$c_name</strong>";
				}
				if (!empty($c_url)) {
					print "<br /><a href=\"$c_url\">$c_url</a>\n";
				}
				print "</p>\n";
				if (date("H:i:s", $datetime)==="00:00:00") {
					$showdate = "";	
				}
				else {
					$showdate = date("j F Y @ G:i",$datetime);
				}
				print "<div style=\"float: right; text-align: right; margin: 5px 10px 5px 10px;\"><em class=\"date\">$showdate</em></div>\n";
				print "<div style=\"clear: both;\"></div></div>\n";
				print "<div style=\"margin: 10px 30px 10px 30px\">\n";
				print "$c_comment";
				print "</div></div>\n";
			}
		}
		else {
			print "<p align=\"center\" style=\"font-weight: bold\">There haven't been any comments on this picture yet.</p>\n\n";
		}

		// comment posting shizzy
		$submitted = true;
		if (!empty($_POST)){
			$comment_img = $_POST['img'];
			$comment_name = stripslashes($_POST['name']);
			$comment_email = stripslashes($_POST['email']);
			$comment_url = stripslashes($_POST['url']);
			$comment = stripslashes($_POST['comment']);
			$notify = $_POST['notify'];
			$ip = $_SERVER['REMOTE_ADDR'];
			$host = gethostbyaddr($ip);
			foreach($banned as $ban_addr) {
				if (preg_match("/^$ban_addr/i", $ip) || preg_match("/^$ban_addr/i", $host)) {
					print "<p align=\"center\" style=\"color: red; font-weight: bold\">Your IP address ($ip) or host ($host) has been banned ($ban_addr).</p>
<p align=\"center\" style=\"color: red; font-weight: bold\">If you believe you have received this message in error, contact the <a href=\"mailto:$admin_mail\">site administrator</a>.</p>\n";
					$submitted = false;
				}
			}
			if (empty($comment)) {
				print "<p align=\"center\" style=\"color: red; font-weight: bold\">You have to fill in a comment!</p>\n";
				$submitted = false;
			}
		}
		else {
			$submitted = false;
		}
		if (!$submitted) {
			$comment_info = explode("|**|", $_COOKIE['commentinfo']);
			print "<form name=\"pic-comments\" action=\"".$_SERVER['REQUEST_URI']."\" method=\"post\">\n";
			print "<center><table border=\"0\" cellpadding=\"5\" cellspacing=\"5\">
				<tr><td valign=\"top\" align=\"left\"><strong>Name:</strong></td>
				<td valign=\"top\" align=\"left\"><input type=\"text\" class=\"comment_input\" name=\"name\" value=\"".$comment_info[0]."\" /></td></tr>
				<tr><td valign=\"top\" align=\"left\"><strong>Your Email:</strong></td>
				<td valign=\"top\" align=\"left\"><input type=\"text\" class=\"comment_input\" name=\"email\" value=\"".$comment_info[1]."\" /></td></tr>
				<tr><td valign=\"top\" align=\"left\"><strong>Your URL:</strong></td>
				<td valign=\"top\" align=\"left\"><input type=\"text\" class=\"comment_input\" name=\"url\" value=\"".$comment_info[2]."\" /></td></tr>
				<tr><td valign=\"top\" align=\"left\"><strong>Comment:</strong><br /><span style=\"color: red; font-size: smaller;\">(required)</span></td>
				<td valign=\"top\" align=\"left\"><textarea cols=\"50\" rows=\"5\" class=\"comment_text\" name=\"comment\">$comment</textarea></td></tr>
				<tr><td colspan=\"2\" valign=\"middle\" align=\"center\"><input class=\"comment_input\" type=\"submit\" value=\"Submit Comment!\" /></td></tr>
				<tr><td valign=\"top\" align=\"left\"><strong>Notify me of<br />future comments?</strong></td>
				<td valign=\"middle\" align=\"left\">
				<input type=\"radio\" name=\"notify\" value=\"1\"";
			if ($notify == 1) {
				print " checked=\"checked\"";
			}
			print " />Yes
				<input type=\"radio\" name=\"notify\" value=\"0\"";
			if ($notify ==0) {
				print " checked=\"checked\"";
			}
			print " />No
				</td></tr>
				<tr><td style=\"font-size: smaller; color: #555555\" colspan=\"2\" valign=\"middle\" align=\"center\">
				HTML is not supported- use this BBCode instead:<br /><br /><code>
				[url=http://...]linkname[/url]<br />
				[b]<strong>bold</strong>[/b]<br />
				[i]<em>italic</em>[/i]<br />
				[u]<u>underline</u>[/u]<br />
				[s]<s>strikethrough</s>[/s]<br />
				</code><br />
				Newlines are automagically converted.
				</td></tr>
				</table></center>\n";
			print "</form>\n";
		}
		else { // everything has been filled out correctly
			$cmt_date = date("Y-m-d H:i:s", time());
			$comment_name = htmlspecialchars($comment_name, ENT_QUOTES);
			$comment_email= htmlspecialchars($comment_email, ENT_QUOTES);
			$comment_url= htmlspecialchars($comment_url, ENT_QUOTES);
			$this_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	
			if ($block_robots) {
				# check to verify that the session variable set on the
				# comment view page is set, if not, then assume a robot
				# is doing the submitting
				if (!isset($_SESSION['from_web'])) {
					return disallow_post($comment_name, $comment_email, $comment_url, $host, $ip, $cmt_date, $item, $comment, "Robot apocalypse is now.");
				}
			}

			if ($akismet_key) {
				# first try and include the akismet.class.php file
				require_once('akismet.class.php');
				$test = array(
					'author' => $comment_name,
					'email' => $comment_email,
					'website' => $comment_url,
					'body' => $comment,
					'permalink' => $this_url
				);
				$akismet = new Akismet(
					sprintf('http://%s%s', $_SERVER['SERVER_NAME'], $_SERVER['SCRIPT_NAME']),
					$akismet_key,
					$test
				);
				if ($akismet->isSpam()) {
					return disallow_post($comment_name, $comment_email, $comment_url, $host, $ip, $cmt_date, $item, $comment, "Akismet reported comment most likely spam");
				}
			}

			# if there is a link in an href which is the same as the
			# comment_url, it's most likely spam
			if (preg_match("/href=['\"]?(.*?)[\/]?['\"]?>/i", $comment, $matches) && (strpos($matches[1], $comment_url) || $matches[1] === $comment_url)) {
				return disallow_post($comment_name, $comment_email, $comment_url, $host, $ip, $cmt_date, $input, $comment, "Comment URL also found in comment boy.");
			}
			
			# if the comment contains certain words, disallow it
			foreach ($banned_words as $banned_word) {
				if (preg_match("/$banned_word/i", $comment) > 0) {
					return disallow_post($comment_name, $comment_email, $comment_url, $host, $ip, $cmt_date, $input, $comment, "'$banned_word' found in comment body.");
				}
				if (preg_match("/$banned_word/i", $comment_url) > 0) {
					return disallow_post($comment_name, $comment_email, $comment_url, $host, $ip, $cmt_date, $input, $comment, "'$banned_word' found in comment URL.");
				}
			}
			
			$comment = nl2br(htmlspecialchars($comment, ENT_QUOTES));

			// make sure this will ba valid html....
			if (substr_count($comment, "[url") != substr_count($comment, "[/url]")) {
				$comment = preg_replace("/\[url=(.*?)\]/", "", $comment);
				$comment = str_replace("[/url]", "", $comment);	
			}
			if (substr_count($comment, "[b]") != substr_count($comment, "[/b]")) {
				$comment = str_replace("[b]", "", $comment);
				$comment = str_replace("[/b]", "", $comment);
			}
			if (substr_count($comment, "[i]") != substr_count($comment, "[/i]")) {
				$comment = str_replace("[i]", "", $comment);
				$comment = str_replace("[/i]", "", $comment);
			}
			if (substr_count($comment, "[u]") != substr_count($comment, "[/u]")) {
				$comment = str_replace("[u]", "", $comment);
				$comment = str_replace("[/u]", "", $comment);
			}
			if (substr_count($comment, "[s]") != substr_count($comment, "[/s]")) {
				$comment = str_replace("[s]", "", $comment);
				$comment = str_replace("[/s]", "", $comment);
			}

			// allow bbcode
			$comment = preg_replace("/\[url=(.*?)\]/", "<a href=\"\\1\">", $comment);
			$comment = str_replace("[/url]", "</a>", $comment);
			$comment = str_replace("[b]", "<strong>", $comment);
			$comment = str_replace("[/b]", "</strong>", $comment);
			$comment = str_replace("[i]", "<em>", $comment);
			$comment = str_replace("[/i]", "</em>", $comment);
			$comment = str_replace("[u]", "<u>", $comment);
			$comment = str_replace("[/u]", "</u>", $comment);
			$comment = str_replace("[s]", "<s>", $comment);
			$comment = str_replace("[/s]", "</s>", $comment);
			if (empty($comment_email)) {
				$notify = 0;
			}
			
			// a comment has been submitted, send the admin an email letting them know
			$to = $admin_mail;
			$subject = "Pic comment submitted";
			$out = "User information:

From:            $comment_name <$comment_email>
Url:             $comment_url
Hostname:        $host <$ip>
Time submitted:  $cmt_date
				
Comment made to: $item
Comment:
-----------
$comment




You can view this in it's entirety here:
$this_url\n";

			$headers = "From: $comment_name <$comment_email>";
			$headers = preg_replace('/\n|\r|(\r\n)/m', " (newline) ", $headers);
			$headers .= "\r\n";
			
			# if there are any instances of " (newline ) " in the header, 
			# just quit
			if (strpos($headers, $headcheese)) {
				return disallow_post($comment_name, $comment_email, $comment_url, $host, $ip, $cmt_date,  $input, $commenti, "Attempted to forge message headers.");
			}
			
			mail($to, $subject, html_entity_decode(stripslashes($out), ENT_QUOTES), $headers);

			$sent_email = array();		
			// send everyone who wants to be notified an email as well
			foreach ($notify_email as $to_notify){
				if (!in_array($to_notify, $sent_email)) {
					$to = "$to_notify";
					$subject = "A new comment has been made....";
					$out = "You previously requested to be notified when new comments are made on the following picture: 
						$this_url

						The new comment was posted on $cmt_date by $comment_name $comment_email $comment_url

						-----------------------------------
						$comment\n\n";
					mail($to, $subject, html_entity_decode(stripslashes($out), ENT_QUOTES), "");
					$sent_email[] = $to_notify;
				}
			}
		
			# check to see	if this element already exists in the
			# elements table, if not, insert it
			$element_query = do_query("SELECT id FROM comments_elements WHERE comments_elements.name='$input'");
			if (mysql_num_rows($element_query) < 1) {
				do_query("INSERT INTO comments_elements(name) values ('$input')");
				printf("\n<!-- New insert '$input' has id %d -->\n", mysql_insert_id());
				$element_query = do_query("SELECT id FROM comments_elements WHERE comments_elements.name='$input'");
			}
			$element_id = mysql_result($element_query, 0);

			# add  this  comment  to the comments  table
			$element_id = mysql_real_escape_string($element_id);
			$cmt_date = mysql_real_escape_string($cmt_date);
			$comment_name = mysql_real_escape_string($comment_name);
			$comment_email = mysql_real_escape_string($comment_email);
			$comment_url = mysql_real_escape_string($comment_url);
			$comment = mysql_real_escape_string($comment);
			$host = mysql_real_escape_string($host);
			$notify = mysql_real_escape_string($notify);
			do_query("INSERT INTO comments_comments(element_id, date, name, email, url, comment, hostname, notify) VALUES ('$element_id', '$cmt_date', '$comment_name', '$comment_email', '$comment_url', '$comment', '$host', '$notify')");
			
			print "<p align=\"center\" style=\"font-weight: bold\">Thank you for posting a comment	:)</p>\n\n";
			print "<p align=\"center\" style=\"font-weight: bold\"><a href=\"".$_SERVER['REQUEST_URI']."\">Click here to view your comment.</a></p>";

		}
	}
}

// given image, get number of comments it has received
function num_comments($input) {
	global $comments_op, $dbh;
	$input = mysql_real_escape_string($input);
	if($comments_op) {
		$results = do_query("SELECT comments_comments.id FROM comments_comments, comments_elements WHERE comments_elements.name='$input' and comments_comments.element_id=comments_elements.id");
		$rows = mysql_num_rows($results);
	}
	else {
		$rows = 0;
	}
	return $rows;
}

// default page display
function default_disp() {
	global $rootdir, $pics, $pics_me, $fp_img, $header_file, $footer_file,	$builtin_css, $gallery_title, $comments_op, $dbh;

	if ($fp_img) {
		// pictures from the array at the beginning of the file,
		// (with descriptions)
		print "<a name=\"pics-desc\"></a>\n";
		print "<div class=\"hr\"><p class=\"leftside\">Detailed Pics</p><div style=\"clear: both;\"></div></div>\n\n";
		print "<center><table border=\"0\" width=\"90%\" cellpadding=\"0\" cellspacing=\"15\">\n";
		foreach ($pics_me as $x) {
			$img = $rootdir.$x['path'].$x['img'];
			$alt = $x['alt'];
			$date = $x['date'];
			$mesg = $x['desc'];
			$desc = "<strong>$alt</strong> <em class=\"date\">( $date )</em><br />$mesg";
			print_img($img, $desc, $comments_op);
		}
		print "</table></center>\n\n";
	}

	// galleries
	print "<a name=\"pics-galleries\"></a>\n";
	print "<div class=\"hr\"><p class=\"leftside\">Galleries</p><div style=\"clear: both;\"></div></div>\n\n";
	print "<blockquote>\n";
	// get array of all subdirectories
	$level_one = list_sdir($rootdir.$pics);
	$h = date_sort($level_one);
	foreach($h as $i) {
		print_dir_name($i);
	}
	print "</blockquote>\n\n";
}


// mysql kludge functions
function db_connect() {
	// this may run many times, but that shouldn't be a problem as
	// PHP will reuse existing MySQL connections, if they exist
	// will return the database handle
	global $dbhost, $dbuser, $passwd, $dbname, $footer_file, $builtin_css;	
	$dbh = mysql_connect($dbhost, $dbuser, $passwd);
	if (!$dbh) {
		print mysql_error();
		if ($footer_file) {
			include($footer_file);
		}
		elseif ($builtin_css) {
			print_footer();
		}
		exit;
	}
	mysql_select_db($dbname, $dbh);
	return $dbh;
}
function do_query($query) {
	// will connect to the database and run the provided query
	// it is expected that this query has already had all its
	// naughty bits escaped.
	global $dbh, $dbhost, $dbuser, $passwd, $dbname, $footer_file, $builtin_css;
	if (!$dbh) {
		$dbh = db_connect();
	}
	$results = mysql_query($query, $dbh);
	if (!$results) {
		print mysql_error();
		if ($footer_file) {
			include($footer_file);
		}
		elseif ($builtin_css) {
			print_footer();
		}
		exit;
	}
	return $results;
}
if ($comments_op) {
	$dbh = db_connect();
}

// Handle the header function, either by specified file or by using the default
if ($header_file) {
	include($header_file);
}
elseif ($builtin_css) {
	builtin_css();
	if ($gallery_title) {
		print "\n\n<a href=\"".$_SERVER["SCRIPT_URI"]." \" class=\"pagetitle\">$gallery_title</a>\n";
	}
}
if ($input = $_GET['dir']) {
	if ((file_exists($rootdir.$input))&&(substr($input, 0,strlen($pics))===$pics)&&($input!=$pics)&&(strpos($input, "..")===false)&&(strpos($input, $cachedir)===false)){
		$pos = strrpos($input, "/");
		$link = substr($input, 0, $pos);
		$info = @file($rootdir.$input."/dirinfo.txt");
		if (!empty($info[0])) {
			$dname = @array_shift($info);
			if (strlen($info[0])>4) {
				$ddate = "<em class=\"date\"> - ".@array_shift($info)."</em>";
			}
			$ddesc = @join(" ",$info);
		}
		else {
			$dname = substr($input, $pos+1);;
		}
		print "\n<div class=\"hr\">\n";
		print "  <p style=\"float: left; margin: 3px 0px 0px 10px;\">\n    <a class=\"inflate\"  href=\"".$_SERVER['PHP_SELF']."?dir=$link\">Up one level</a>\n  </p>\n";
		print "  <p style=\"float: right; text-align: right; font-size: large; margin: 3px 10px 0px 0px;\">$dname</p>\n";
		print "  <div style=\"clear: both;\"></div>\n</div>\n";
		if (!empty($ddesc)) {
			print "<div style=\"margin: 10px 75px 20px 75px;\">\n";
			print "<em>$ddesc</em>\n";
			print "<div style=\"text-align:center;\">$ddate</div>\n";
			print "</div>\n";
			print "\n<hr align=\"center\" size=\"1\" width=\"85%\" noshade=\"noshade\" />\n";
		}
		print "\n<div style=\"align:center;margin:10px 30px 10px 30px;\">\n";

		$subdir = list_sdir($rootdir.$input);
		$imgs = list_files($rootdir.$input);

		if ($sort_method == "mod") {
			$j = modification_sort($subdir);
		}
		else {
			$j = date_sort($subdir);
		}
		foreach($j as $k) {
			print_dir_name($k);
		}
		if ((!empty($subdir))&&(!empty($imgs))) {
			print "\n\n<br /><hr align=\"center\" size=\"1\" width=\"85%\" noshade=\"noshade\" /><br />\n\n";
		}

		if ($file_sort_method == "mod") {
			$imgs = modification_sort($imgs);
		}
		else {
			$imgs = date_sort($imgs);
		}
		foreach ($imgs as $x) {
			print_img($x, "", $comments_op);
		}
		print "\n</div>\n";
		print "  <div style=\"clear: both;\"></div>\n\n";
		print "\n<div class=\"hr\">\n";
		print "  <p style=\"float: left; margin: 3px 0px 0px 10px;\">\n    <a class=\"inflate\"  href=\"".$_SERVER['PHP_SELF']."?dir=$link\">Up one level</a>\n  </p>\n";
		print "  <p style=\"float: right; text-align: right; font-size: large; margin: 3px 10px 0px 0px;\">$dname</p>\n";
		print "  <div style=\"clear: both;\"></div>\n</div>\n";
	}
	else {
		if (function_exists('alt_disp')) {
			alt_disp();
		}
		else {
			default_disp();
		}
	}
}
else if ($input = $_GET['pic']) {
	if (file_exists($rootdir.$input)&&(substr($input, 0,strlen($pics))===$pics)&&($input!=$pics)){
		print_pic($input);
	}
	else {
		if (function_exists('alt_disp')) {
			alt_disp();
		}
		else {
			default_disp();
		}
	}
}
else {
	if (function_exists('alt_disp')) {
		alt_disp();
	}
	else {
		default_disp();
	}
}
// Handle the footer function, either by specified file or by using the default
if ($footer_file) {
	include($footer_file);
}
elseif ($builtin_css) {
	print_footer();
}

?>
