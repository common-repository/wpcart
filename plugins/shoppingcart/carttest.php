<?php
/*
WordPress Cart Testing
http://www.wordpresscart.org
*/

/*  Copyright 2005, 2006  Dave Merwin and Michael Calabrese  (email : dave@madeblue.com m2calabr@dunamisdesign.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$testlist = array(
'NonUser' => array(
		'nonUser_Profile'
	,	'nonUser_Cart'
	)
,'Admin' => array(
		'admin_AddProduct'
	)
,'User' => array(
		'user_CartTest'
	)
);
$runtests = true;
$debug=false;
define ('BASEURL',"http://wordpress.localhost.com/");
define ('PLUGINPATH', ABSPATH . "wp-content/plugins/shoppingcart/");
define ('ADMINLOGIN',"admin");
define ('ADMINPASS',"f03115");
define ('USERLOGIN',"m2calabr");
define ('USERPASS',"093a16b");

//--------------------------------------------------
class test 
{
	/**
	* Name of the test
	* @var string
	*/
	var $name = null;
	var $curl = false;
	var $singleCurl = true;
	
	var $debug=false;
	var $debuglevel=1;
	var $debugtext='';
	 /**
     * Adds the message to the debug text if debug is turned
     * on and we are at the correct level
     *
     * @param int   $arg1   the debug level when when message should
     *                      appear
     * @param string $arg2  the message to be added
     *
     * @access public
     * @see Net_Sample::$foo, Net_Other::someMethod()
     * @since Method available since Release 0.9.3
     */     
	function debugMsgAdd($level,$message) {
		if ($this->debug && $this->debuglevel >= $level) {
			$this->debugtext .= $message;
		}
	}
	function login($user,$pass,$cookiejar,$search) {
		//These options are need to keep Cookies during the connection
		$this->debugMsgAdd(2,"LOGIN: USER $user PASS: $pass JAR: $cookiejar<br />");
		$this->debugMsgAdd(3,"LOGIN: curl: {$this->curl}<br />");
		curl_setopt($this->curl, CURLOPT_HEADER,0);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE,PLUGINPATH . "$cookiejar");
		curl_setopt($this->curl, CURLOPT_COOKIEJAR,PLUGINPATH . "$cookiejar");
		$param = "log={$user}&pwd={$pass}";
		$param .= "&redirect_to=" . BASEURL . "wp-admin/";
		return $this->quickSearch(BASEURL . 'wp-login.php',$search,$param);
	}	
	
	function quickResult($url,$postparams=null) {
		$defined_vars = get_defined_vars();
		$this->debugMsgAdd(3,"quickResult: URL: $url POST: {$postparams}<br />");
		$this->debugMsgAdd(3,"quickResult: curl: {$this->curl}<br />");
		if ($this->singleCurl) { $this->curl = curl_init(); } 
		if ($postparams) {
			curl_setopt($this->curl, CURLOPT_POST,1);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS,$postparams);	
		}
		curl_setopt($this->curl, CURLOPT_URL,$url);
		curl_setopt($this->curl, CURLOPT_USERAGENT, $defined_vars['HTTP_USER_AGENT']);
		
		//set options
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($this->curl, CURLOPT_TIMEOUT,  120);
		
		$result=curl_exec ($this->curl);
		if ($this->singleCurl) curl_close ($this->curl);
		
		$this->debugMsgAdd(3,'<div style="width:700px;height:200px;overflow:scroll;border: #FF000 solid 2px;">' . htmlspecialchars($result) . '</div>');
		return $result;
	}
	function quickSearch($url,$regexp,$postparams=null) {
		$defined_vars = get_defined_vars();
		$this->debugMsgAdd(3,"quickResult: URL: $url POST: {$postparams}<br />");
		$this->debugMsgAdd(3,"quickSearch: curl: {$this->curl}<br />");
		if ($this->singleCurl) $this->curl = curl_init();
		if ($postparams) {
			curl_setopt($this->curl, CURLOPT_POST,1);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS,$postparams);	
		}
		curl_setopt($this->curl, CURLOPT_URL,$url);
		curl_setopt($this->curl, CURLOPT_USERAGENT, $defined_vars['HTTP_USER_AGENT']);
		
		//set options
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($this->curl, CURLOPT_TIMEOUT,  120);
		
		$result=curl_exec ($this->curl);
		if ($this->singleCurl) curl_close ($this->curl);
		
		$this->debugMsgAdd(3,'<div style="width:700px;height:200px;overflow:scroll;border: #FF000 solid 2px;">' . htmlspecialchars($result) . '</div>');
		
		if (ereg($regexp,$result)) {return true;}
		else                       {return false;}
	}
}
//--------------------------------------------------
class nonUser_Profile extends test {
	var $name = 'nonUser_Profile';
	var $title = 'Accessing Profile';
	function test() {
	return $this->quickSearch(BASEURL . '?cart_profile=1','You are not logged in');
	}
}
//--------------------------------------------------
class nonUser_Cart extends test {
	var $name = 'nonUser_Profile';
	var $title = 'Accessing Cart';
	function test() {
	return $this->quickSearch(BASEURL . '?cart=1','You are not logged in');
	}
}
//--------------------------------------------------
class admin_AddProduct extends test {
	var $name = 'admin_AddProduct';
	var $title = 'Adding Product';
	var $singleCurl = false;
	var $partname = '';
	var $partid = null;
	static $ob_count = 0;
	function init() {
		$this->debugMsgAdd(1,"{$this->name}: Init admin cart test<br />\n");
		//Add parts
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_HEADER,0);
		curl_setopt($this->curl, CURLOPT_AUTOREFERER,1);
		curl_setopt($this->curl, CURLOPT_COOKIESESSION,1);
	}
	function admin_login() {
		$this->debugMsgAdd(1,"{$this->name}: Admin Login<br />\n");
		return $this->login (ADMINLOGIN,ADMINPASS,"admin_cjar_{$this->ob_count}.txt",'Cart');
	}	
	function addPart() {
		$this->debugMsgAdd(1,"{$this->name}: Adding new part<br />\n");
		$ob_count++;
		//make a draft so we can positivly catch the post id (partid)
		$param  = 'user_ID=1';
		$param .= '&action=post';
		$param .= '&submit=savedraft';
		$title = 'Auto Product '.$ob_count;
		$this->partname = $title;
		$param .= '&post_title=' . urlencode($title);
		$post = $this->quickResult(BASEURL . 'wp-admin/post.php',$param);
		preg_match("/(post=)([0123456789]+)(' title='Edit this draft'>{$title})/",$post,$matches);
		
		$this->partid=$matches[2];
		$this->debugMsgAdd(1,"{$this->name}: Added part {$this->partid}<br />\n");
		//turn it into a publisted post
		$param  = 'user_ID=1';
		$param .= '&action=editpost';
		$param .= '&post_author=1';
		$param .= '&post_ID='.$this->partid;
		$param .= '&publish=publish';
		$param .= '&post_status=publish';
		$param .= '&submit=publish';
		$param .= '&post_title=' . urlencode($title);
		$param .= '&content=' . urlencode("This is a test project $ob_count Yahoo.....");
		$param .= '&cart_isprod=Yes';
		$param .= '&cart_price='. urlencode('' . $ob_count * 24.24);
		$param .= '&cart_url='. urlencode('http://yahoo.com');
		$param .= '&cart_genname='. urlencode('Yahoo thing');
		return $this->quickSearch(BASEURL . 'wp-admin/post.php','Dashboard',$param);
	}
	function deletePart() {
		$this->debugMsgAdd(1,"{$this->name}: Deleting part {$this->partid}<br />\n");
		curl_setopt($this->curl, CURLOPT_REFERER,BASEURL . 'wp-admin/edit.php');
		return $this->quickSearch(BASEURL . 'wp-admin/post.php?action=delete&post='.$this->partid,'Last 15');
	
	}	
	
	function test() {
		$this->debugMsgAdd(1,"{$this->name}: Running test<br />\n");
		$this->init();
		
		//Go through the steps
		$ok = $this->admin_login();
		if (!$ok) return false;
		$ok = $this->addPart();
		if (!$ok) return false;
		$ok = $this->deletePart();
		
		//All Done clean up
		curl_close ($this->curl);
		return $ok;
	}
}
//--------------------------------------------------
class user_CartTest extends test {
	var $name = 'user_CartTest';
	var $title = 'User Cart Test';
	var $singleCurl = false;
	var $item1=null;
	var $item2=null;
	var $item3=null;
	static $ob_count = 0;
	function init() {
		$this->debugMsgAdd(1,"{$this->name}: Initializing<br />\n");
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_HEADER,0);
		curl_setopt($this->curl, CURLOPT_AUTOREFERER,1);
		curl_setopt($this->curl, CURLOPT_COOKIESESSION,1);
		
		$this->debugMsgAdd(2,"{$this->name}: Adding part to DB<br />\n");
		$this->item1 = new admin_AddProduct();
		$this->item1->debug=$this->debug;		
		$this->item1->init();
		$ok = $this->item1->admin_login();
		$ok = $this->item1->addPart();
		$this->debugMsgAdd(3,"{$this->item1->debugtext}<br />\n");
		$this->debugMsgAdd(2,"{$this->name}: Added part {$this->item1->partid}<br />\n");
	}
	function user_login() {
		$this->debugMsgAdd(1,"{$this->name}: UserLogin<br />\n");
		return $this->login (USERLOGIN,USERPASS,'user_cjar_'. $this->ob_count . '.txt','Your Profile');
	}	
	function putCart($partid,$search) {
		$this->debugMsgAdd(1,"{$this->name}: Adding part {$partid} to cart<br />\n");
		return $this->quickSearch(BASEURL . "?p={$partid}&addcart=1",$search);
	}
	function get_lineitem($partname) {
		$cart = $this->quickResult(BASEURL . "?cart=1");
		preg_match("/(actiondelete\(')([0123456789]+)('.*{$partname})/",$cart,$matches);
		return $matches[2];
	}
	function updateQty($partid,$qty) {
		$this->debugMsgAdd(1,"{$this->name}: Changing Qty on {$partid} to {$qty}<br />\n");
		$lineitem_id=$this->get_lineitem($this->item1->partid);
		return $this->quickSearch(BASEURL . "?qty_{$lineitem_id}={$qty}&formaction=update&updatecart=1", "name=\"qty_{$lineitem_id}\" value=\"$qty\"");
//		return true;
	}
	function delete($partid) {
		$this->debugMsgAdd(1,"{$this->name}: Deleteing {$partid} from cart<br />\n");
		$lineitem_id=$this->get_lineitem($this->item1->partid);
		$result = $this->quickResult(BASEURL . "?deleteitem={$lineitem_id}&formaction=delete&updatecart=1");
		//We do NOT want to find the following....
		$matchlength = ereg("name=\"qty_{$partid}\"",$result);
		if ($matchlength==0) {return true;}
		else                 {return false;}
	}
	function clear_cart() {
		$this->debugMsgAdd(1,"{$this->name}: Clearing the user's cart<br />\n");
		$result =  $this->quickSearch(BASEURL . "?formaction=reset&updatecart=1");
		//We do NOT want to find the following....
		$matchlength = ereg("name=\"qty_",$result);
		if ($matchlength==0) {return true;}
		else                 {return false;}
	}
	function request_quote() {
		$this->debugMsgAdd(1,"{$this->name}: Requesting a quote<br />\n");
		return $this->quickSearch(BASEURL . "?formaction=makequote&updatecart=1", "Thanks for your request");
	}
	function cleanup() {
		$this->debugMsgAdd(2,"{$this->name}: Deleting part {$this->item1->partid}<br />\n");
		$this->item1->deletePart();
	}
	function test() {
		$this->debugMsgAdd(1,"{$this->name}: Running test<br />\n");
		$this->init();
		$ok = $this->user_login();
		if (!$ok) return false;
		$ok = $this->clear_cart();
		if (!$ok) return false;
		$ok = $this->putCart($this->item1->partid,$this->item1->partname);
		if (!$ok) return false;
		/* Quick list of current user actions:
				delete item
				change item qty
				clear cart
				request quote
		*/
		$ok = $this->updateQty($this->item1->partid,383);
		if (!$ok) return false;
		$ok = $this->delete($this->item1->partid);
		if (!$ok) return false;
		$ok = $this->request_quote();
		if (!$ok) return false;
		$this->cleanup();
		return $ok;
	}
}

//--------------------------------------------------
//Run the tests and get the debug information if asked for
$testinglist = $_POST['testlist'];
$debuglist = $_POST['debuglist'];
foreach ($testinglist as $testobj => $value) {
	$atest = new $testobj ();
	(isset ($debuglist[$testobj])) ? $atest->debug=true : $atest->debug=false;
	//Set the color that will be displayed on the table
	if ($atest->test()) {
		$testinglist[$testobj]='green';
	} else {
		$testinglist[$testobj]='red';
	}
	$debugtext[$testobj] = '&nbsp;' . $atest->debugtext;
	$atest=null;
}
?>

<div class="wrap">
<h2>Cart Testing Page</h2>
This page is for developers so that when code changes are made the functionallity of the system can be tested.

<form method="post">
<table border="1">
<thead><th>Run?</th><th>Debug?</th><th>Pass/Fail</th><th>Name</th><th>Description</th></thead>
<?
	foreach ($testlist as $group => $tests) {	
		print "<tr><td align=\"center\" colspan=\"5\"><i><b>$group</b></i></td></tr>";
		foreach ($tests as $testobj) {
			$atest = new $testobj ();
			print "<tr>";
			(isset ($testinglist[$testobj])) ? $chk = "checked" : $chk='';
			print "<td align=\"center\"><input type=\"checkbox\" name=\"testlist[$testobj]\" $chk/></td>";
			(isset ($debuglist[$testobj])) ? $chk = "checked" : $chk='';
			print "<td align=\"center\"><input type=\"checkbox\" name=\"debuglist[$testobj]\" $chk/></td>";
			(isset ($testinglist[$testobj])) ? $runcolor=$testinglist[$testobj] : $runcolor="lightgrey";
			print "<td width=\"50px\" bgcolor=\"$runcolor\">&nbsp;</td>";
			print "<td>{$atest->name}</td>";
			print "<td width=\"100%\">{$atest->description}</td>";
			print "</tr>";
			if ( (isset ($debuglist[$testobj])) && (strlen($debugtext[$testobj]) !=0) ) {
				print "<tr><td align=\"center\" colspan=\"5\">{$debugtext[$testobj]}</td></tr>";
			}
			$atest=null;
		}
	}
?>
</table>
<input type="submit" />
</form>
</div>

