<?php 
/*
 ________          _______           ________      ________      _______           _______      
|\_____  \        |\  ___ \         |\  _____\    |\  _____\    |\  ___ \         |\  ___ \     
 \|___/  /|       \ \   __/|        \ \  \__/     \ \  \__/     \ \   __/|        \ \   __/|    
     /  / /        \ \  \_|/__       \ \   __\     \ \   __\     \ \  \_|/__       \ \  \_|/__  
    /  /_/__        \ \  \_|\ \       \ \  \_|      \ \  \_|      \ \  \_|\ \       \ \  \_|\ \ 
   |\________\       \ \_______\       \ \__\        \ \__\        \ \_______\       \ \_______\
    \|_______|        \|_______|        \|__|         \|__|         \|_______|        \|_______|                                                                       

*/


/**
*   ==========================================================
*
*	@author      www.zeffee.com
*	@date        2015-12-11
*	@version     2.0
*
*	@var string  $sourcefile 源码文件名
*	@var string  $resultfile 密文文件名(自动新建)
*	@var int 	 $mode       加密模式 	0 => 简单模式(无法阅读) 
*									 	1 => 严格模式(无法想象) 	   
*
*/

$sourcefile="source.php";  
    	     
$resultfile="output.php";	

$mode = 0;	   




/*   
 *	注意: 1. 可以有注释内容，但除了注释内容，其他内容请勿加入 "//" 和 "#" (特别留意正则)，否则加密后无法运行。 
 *		  2. 此版本支持加密PHP与html混合的PHP文件。但符合以下条件的将会出错。
 				--如果一个PHP代码段调用了另一个PHP代码段的变量,函数等等将会出错。
 				--如果有两个PHP代码断完全一模一样将会出错。
 *		  
 *

===========================Mcrypt Class=================================
*/

/**
*   class Mcrypt  加密类
*	@author       www.zeffee.com
*	@date         2015-12-11
*	@version      2.0
*
*/
class Mcrypt
{
	/**
	*===========================================
		*function Getsource  获取源码
	*===========================================
	*
	*	@param  string $sourcefile 源码文件路径
	*	@return string 			   源码
	*
	*/
	public function Getsource($sourcefile)
	{
		if(!$sourcefile){exit("Please input the sourcefile!");}
		$fp=fopen($sourcefile, "r");
		$text="";
		while(!feof($fp)) 
		{ 
		$text.=fgets($fp); 
		} 
		fclose($fp); 
		return $text;	
	}



	/**
	*========================================
		*function Getphp  获取PHP代码
	*========================================
	*
	*	@param  string $text   源码
	*	@return array 		   所有PHP代码内容
	*
	*/
	public function Getphp($text)
	{
		$matchres=array();
		//判断是否有完整的开始结束符并取PHP代码
		if(preg_match_all('/(<\?php|<\?)(.*)\?>/ismU', $text, $matchres))
		{
			if($matchres[2])
			{
				$matchres=$matchres[2];
			}
		}
		else
		{
			preg_match_all('/(<\?php|<\?)(.*)/ism', $text, $matchres);
			$matchres=$matchres[2];
		}
		return $matchres;
	}



	/**
	*========================================
		*function Ttext  去掉注释、换行
	*========================================
	*
	*	@param  string $text 源码
	*	@return string 		 处理之后文本
	*
	*/
	public function Ttext($text)
	{
		return preg_replace(array("/([^:]\/\/| #)[^\n]*/","/[\n\r]/"), array("",""), $text);
	}



	/**
	*=====================================
	   	*function Change 混淆ascii
	*=====================================
	*
	*   @param  string  $con       源码内容
	*	@param  int     $function  0-->只是混淆变量名
	*							   1-->加上要混淆函数名 
	*	@param  string  $deviation 偏移量(仅在function为1时候调用,为了防止出现重复的名字)
	*
	*	@return string             返回混淆后的内容
	*
	*/
	public function Change($con,$function=0,$deviation=""){
		//变量名混淆
		//匹配所有变量名
		preg_match_all('/\$[a-z0-9][a-z0-9_]*/i', $con,$out);
		//过滤重复变量名
		$arr=array();
		foreach ($out[0] as $key0 => $value0) {
			if(! in_array($value0, $arr))
				$arr[]=$value0;
		}
		//开始混淆
		foreach ($arr as $key => $value) {
			$value=substr($value, 1);
			if($value=="this")
				continue;
			$con=preg_replace(array('/\$'.$value.'/','/->'.$value.'/'),array("\$".chr(135+$key).chr(155+$key),"->".chr(135+$key).chr(155+$key)), $con);
		}	

		//函数名混淆
		if($function==1)
		{
			//匹配
			if(preg_match_all('/function.([^(]*)/i', $con,$out1))
			{
				//过滤重复
				$arr=array();
				foreach ($out1[1] as $key1 => $value1) {
					if(! in_array($value1, $arr))
						$arr[]=$value1;
				}
				//开始混淆
				foreach ($arr as $key2 => $value2) {
					$con=preg_replace('/'.$value2.'/', chr(136+$key+$key2).chr(168+$key+$key2).$deviation, $con);
				}
			}	
		}	
		return " ".$con." ";
	}



	/**
	*=============================================
	   		*function Doublemcrypt  多层加密
	*=============================================
	*
	*   @param  string  $text      源码或者一层加密之后的内容
	*   @param  string  $deviaction函数Change偏移量
	*	@return string             返回加密后的内容
	*
	*/	
	public function Doublemcrypt($text,$deviation="")
	{		
		//自定义密钥(英文或者字母)   不得超过32位
		$bosskey=md5("this_is_a_key".date("YmdHis"));	
				
		//第一层des	
		$text=$this->Des($text,$bosskey);
		$text="('".addcslashes($text,'\'\\')."',".chr(200).chr(205)."));";  		

		//混淆处理
		$head='if(!defined("WWW_ZEFFEE_COM")){define("'.chr(200).chr(205).'","'.$bosskey.'");function OO00($a){return gzinflate($a);}function O00O($b){$O000="p"."r"."e"."g"."_"."r"."e"."p"."l"."a"."c"."e";$O000("/ee/e","@".str_rot13("riny").\'($b)\', "ee");}function OO0O($c,$d){return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $d, $c, MCRYPT_MODE_ECB);}OO00("'.gzdeflate("W_W_W.Z_E_F_F_E_E.C_O_M").'");O00O(OO0O';
		$head=$this->Change($head,1,$deviation); 									//混淆所有名字
		//整合数据
		$head.=$text.'}';
		$text=$head;

		//第二层base		
		return ' eval(base64_decode("'.$this->Base($text).'")); ';		
	}



	/**
	*======================================
		   *function Des  DES加密
	*======================================
	*
	*   @param   string  $data     源码
	*	@param   string  $key      加密的密钥	
	*	@return  string            加密内容 
	*
	*/
	public function Des($data,$key)
	{
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB);
	}



	/**
	*======================================
		   *function Base  BASE加密
	*======================================
	*
	*   @param   string  $data     源码
	*	@return  string            加密内容 	
	*
	*/
	public function Base($data)
	{
		return base64_encode($data);
	}	



	/**
	*=======================================
		   *function Mark  标记PHP代码断
	*=======================================
	*
	*   @param   string  $arrayphp php代码段源码
	*   @param   string  $alltext  全部源码
	*	@return  string            标记之后代码 	
	*
	*/
	public function Mark($arrayphp,$alltext)
	{
		foreach ($arrayphp as $keymark => $valuemark) {
			$alltext=str_replace($valuemark, " ".$keymark.$valuemark, $alltext);
		}
		return $alltext;
	}



	/**
	*=======================================
		   *function Mark  替换为密文
	*=======================================
	*
	*   @param   string  $arrayphp php代码段源码
	*   @param   string  $alltext  全部源码
	*	@return  string            加密之后的代码 	
	*
	*/
	public function Replace_Mrypt_Code($arrayphp,$alltext,$mode)
	{
		foreach ($arrayphp as $keymat => $valuemat) 
		{
			//判断模式
			if($mode==1)
			{     
				$alltext=str_replace($keymat.$valuemat, $this->Doublemcrypt($this->Change($valuemat),$keymat), $alltext);
			}
			else
			{
				$alltext=str_replace($keymat.$valuemat, $this->Change($valuemat), $alltext);
			}	
		}
		return $alltext;
	}



	/**
	*======================================
		   *function Infile  写入文件
	*======================================
	*
	*   @param  string  $resultfile     输出文件的路径
	*	@param  string  $text           加密后的代码	
	*
	*/
	public function Infile($resultfile,$text)
	{
		$file=fopen($resultfile, "w");
		fwrite($file, $text);
		fclose($file);
		echo "Done!<br/>File Path:".$resultfile;
	}

	public function __construct()
	{
		error_reporting(E_ALL||~E_NOTICE);
	}
}
/*
=========================End Class=================================
*/


foreach ($sourcefile as $key => $value) {

$mcypt=new Mcrypt();

//获取源码
$alltext=$mcypt->Getsource($value);

//去掉注释、换行
$alltext=$mcypt->Ttext($alltext);

//获取PHP代码
$arrayphp=$mcypt->Getphp($alltext);

//给PHP代码段添加标识符
$alltext=$mcypt->Mark($arrayphp,$alltext);

//替换成加密的代码
$alltext=$mcypt->Replace_Mrypt_Code($arrayphp,$alltext,$mode);

//转换成utf8编码
$alltext=mb_convert_encoding($alltext,"UTF-8","auto");

//写入文件
$mcypt->Infile($resultfile[$key],$alltext);
}

?>
