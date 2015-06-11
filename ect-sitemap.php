<?php 
/*
Plugin Name:ECT SITEMAP
Description:This plugin will generate a SITEMAP PAGE as well as xml sitemap file. Use [ECT_HTML_SITEMAP] to display html sitemap
Author:Andy Chapman
Author URI:http://www.ecommercetemplates.com
Version:1.2
*/
define('PLUGIN_NAME','ect_sitemap_');

register_activation_hook(__FILE__,'ect_sitemap_install');
function ect_sitemap_install()
{
	$XmlFile='sitemap.xml';
	GenerateXmlSiteMap($XmlFile);
	add_option('ect_sitemap_last_update',time());
	add_option('ect_sitemap_excluded_pages','');
	add_option('ect_sitemap_excluded_posts','');
	add_option('ect_sitemap_excluded_prods','');
	add_option('ect_sitemap_is_seo','');
}
add_action('admin_menu','ect_sitemap_nav');

function ect_sitemap_nav()
{
	add_menu_page('ECT SITEMAP','ECT SITEMAP','manage_options','ect_sitemap',PLUGIN_NAME.'fun',plugin_dir_url(__FILE__).'img/ect28x28.png',1009);
}
function ect_sitemap_fun()
{
	echo '<h2>ECT SITEMAP</h2>';
	$LastUpdate=get_option('ect_sitemap_last_update',true);
	$NextUpdate=mktime(date('H',$LastUpdate),date('i',$LastUpdate),date('s',$LastUpdate),date('m',$LastUpdate),date('d',$LastUpdate)+1,date('Y',$LastUpdate));
	echo '<p>Last Update:'.date('d M Y H:i:s',$LastUpdate).'</p>';
	echo '<p>Next Update:'.date('d M Y H:i:s',$NextUpdate).'</p>';
	echo '<p><a href="admin.php?page=ect_sitemap&ect_sitemap_update=1"><input type="button" value="Update Now" /></a></p>';
	$DataArr=DataToArray(1);
	$ProdArr=ListStoreProd1();
	
	$ExcludedPages=get_option('ect_sitemap_excluded_pages');
	$ExcludedPosts=get_option('ect_sitemap_excluded_posts');
	$ExcludedProds=get_option('ect_sitemap_excluded_prods');
	
	$IsSeo=get_option('ect_sitemap_is_seo');
	
	$IsSeo=!empty($IsSeo) ? "checked='checked'" : '';
	
	if(isset($_GET['msg'])) 
		echo '<div id="message" class="updated below-h2"><p>Settings saved successfully .</p></div>';
	
	echo '<form method="post">
			<input type="hidden" name="def" value="1" />';
		echo '<h2>Exclude Page&nbsp;<small>(Mark to exclude page.)</small></h2>';
			if(!empty($DataArr))
			{
				echo '<ul class="admin_inner">';
				asort($DataArr['page']);
				foreach($DataArr['page'] as $p)
				{
					$Chk='';
					if(@in_array($p['PID'],$ExcludedPages))
						$Chk='checked="checked"';
					echo '<li><input type="checkbox" name="exclude[]" value="'.$p['PID'].'" '.$Chk.' /><label>'.$p['PostTitle'].'</label></li>';
				}
				echo '</ul>';
			}
	echo '<h2>Exclude Posts&nbsp;<small>(Mark to exclude posts.)</small></h2>';
			if(!empty($DataArr))
			{
				echo '<ul class="admin_inner">';
				asort($DataArr['post']);
				foreach($DataArr['post'] as $p)
				{
					$Chk='';
					if(@in_array($p['PID'],$ExcludedPosts))
						$Chk='checked="checked"';
					echo '<li><input type="checkbox" name="excludepost[]" value="'.$p['PID'].'" '.$Chk.' /><label>'.$p['PostTitle'].'</label></li>';
				}
				echo '</ul>';
			}
	echo '<h2>Exclude Products&nbsp;<small>(Mark to exclude products.)</small></h2>';
	echo '<ul class="admin_inner">';
			if(!empty($ProdArr))
			{
				asort($ProdArr);
				foreach($ProdArr as $pp)
				{
					$Chk='';
					if(@in_array($pp->pID,$ExcludedProds))
						$Chk='checked="checked"';
					echo '<li><input type="checkbox" name="excludeprod[]" value="'.$pp->pID.'" '.$Chk.' /><label>'.$pp->pName.'</label></li>';
				}
			}
	
	echo '<li><input type="submit" value="Save Changes" /></li></ul>';
	echo '</form>';
	
/*	echo do_shortcode('[ECT_HTML_SITEMAP]');*/
	
	if(!empty($_POST))
	{
		$IsSeo=isset($_POST['ect_sitemap_is_seo']) ? 1 : 0;
		update_option('ect_sitemap_excluded_pages',$_POST['exclude']);
		update_option('ect_sitemap_excluded_posts',$_POST['excludepost']);
		update_option('ect_sitemap_excluded_prods',$_POST['excludeprod']);
		update_option('ect_sitemap_is_seo',$IsSeo);
		echo '<script>window.location="admin.php?page=ect_sitemap&msg=1"</script>';
	}
} 
add_action('wp_footer','ect_sitemap_css');
function ect_sitemap_css()
{
	echo '<link href="'.plugin_dir_url(__FILE__).'css/style.css" rel="stylesheet"/>';
}
add_action('init','ect_sitemap_update_maps');
function ect_sitemap_update_maps()
{	
	MapUpdater();
}
function MapUpdater()
{
	$LastUpdate=get_option('ect_sitemap_last_update',true);
	$NextUpdate=mktime(date('H',$LastUpdate),date('i',$LastUpdate),date('s',$LastUpdate),date('m',$LastUpdate),date('d',$LastUpdate)+1,date('Y',$LastUpdate));
	if(time()>$NextUpdate || isset($_GET['ect_sitemap_update']))
	{	
		$XmlFile='sitemap.xml';
		GenerateXmlSiteMap($XmlFile);
		update_option('ect_sitemap_last_update',time());
		if(isset($_GET['ect_sitemap_update']))
			wp_redirect('admin.php?page=ect_sitemap');
	}
}
add_shortcode('ECT_HTML_SITEMAP','make_ect_html_sitemap');
function make_ect_html_sitemap()
{	
	$Str2='';
	$DataArr=DataToArray();
	if(!empty($DataArr))
	{
		if(isset($DataArr['home']))
			$Str2.=HtmlInnerTag($DataArr['home']);
		
		if(isset($DataArr['post']))
			$Str2.=HtmlInnerTag($DataArr['post'],'Posts');
		
		if(isset($DataArr['page']))
			$Str2.=HtmlInnerTag($DataArr['page'],'Pages');
		
	}
	$D=StorePages();
	$Str2.=HtmlInnerTag($D,'Store');
	
	return $Str2;
}
function GenerateXmlSiteMap($XmlFile)
{
	global $wpdb;
	$fp=fopen('../'.$XmlFile,'w');
	$Str='<?xml version="1.0" encoding="UTF-8"?>
		<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
	$DataArr=DataToArray();
	if(!empty($DataArr))
	{
		if(isset($DataArr['home']))
			$Str.=XmlInnerTag($DataArr['home']);
		
		if(isset($DataArr['post']))
			$Str.=XmlInnerTag($DataArr['post']);
		
		if(isset($DataArr['page']))
			$Str.=XmlInnerTag($DataArr['page']);
			
		$D=StorePages();
		$Str.=XmlInnerTag($D);
		$Str.=GetCats(1);
		$Str.=ListStoreProd('',1);
		
	}
	$Str.='</urlset>';
	fwrite($fp,$Str);
	fclose($fp);
}
function XmlInnerTag($Data)
{
	$Tmp='';
	foreach($Data as $Arr) 
	{
		$Tmp.='<url>
				<loc>'.$Arr['Url'].'</loc>
				<lastmod>'.$Arr['LastModify'].'</lastmod>
			</url>';
	}
	return $Tmp;	
}
function HtmlInnerTag($Data,$Page='Home')
{
	$Tmp='';
	$Tmp.='<div class="header">
			<p class="header-txt">'.$Page.':</p>
		</div><ul class="ect_sm">';
	foreach($Data as $Arr) 
	{	
		
		$Tmp.='<li>
			<a title="'.$Arr['Url'].'" href="'.$Arr['Url'].'">'.$Arr['PostTitle'].'</a>
		';
		if($Arr['PostTitle']=='Products' && $Page=='Store')
		{
			/*$Tmp.='<ul class="ect_sm_sub">';
			$Tmp.=ListStoreProd();	
			$Tmp.='</ul>';*/	
		}
		if($Arr['PostTitle']=='Categories' && $Page=='Store')
		{
			$Tmp.='<ul class="ect_sm_sub">';
			$Tmp.=GetCats();	
			$Tmp.='</ul>';	
		}
	}
	$Tmp.='</li></ul>';
	return $Tmp;
}
function ListStoreProd($Sec='',$Xml='')
{
	global $db_username,$db_password,$db_name,$db_host;
	if($Xml)
		include $_SERVER['DOCUMENT_ROOT']."/vsadmin/db_conn_open.php";
	else
		include "vsadmin/db_conn_open.php";
		
	global $ECTWPDB;
	if(!$ECTWPDB)
	{
		$ECTWPDB=new wpdb($db_username, $db_password, $db_name, $db_host);
	}
	if($ECTWPDB)
	{
		$Tmp='';
		if(!empty($Sec))
			$Cond=' and pSection='.$Sec;
		$ExcludedProds=get_option('ect_sitemap_excluded_prods');
	
		$ProdArr=$ECTWPDB->get_results("select pID,pName,pDateAdded  from products where pDisplay=1 and pInStock>0 $Cond  order by pPrice");
		if(!empty($ProdArr))
		{	
			foreach($ProdArr as $Pp)
			{
				if(@!in_array($Pp->pID,$ExcludedProds)) 
				{
					$Rt=str_replace(' ','-',$Pp->pID);
					if($GLOBALS['usepnamefordetaillinks'])
						$Rt=str_replace(' ','-',$Pp->pName);
						
					if($Xml)
						$Tmp.='<url><loc>'.UM('',str_replace(' ','-',$Pp->pName),'proddetail.php?prod='.$Rt).'</loc><lastmod>'.$Pp->pDateAdded.'</lastmod></url>';
					else
						$Tmp.='<li><a title="'.$Pp->pName.'" href="'.UM('',str_replace(' ','-',$Pp->pName),'proddetail.php?prod='.$Rt).'">'.$Pp->pName.'</a></li>';
				}
			}
		}
		return $Tmp;
	}
}

function ListStoreProd1()
{
	global $db_username,$db_password,$db_name,$db_host;
	include $_SERVER['DOCUMENT_ROOT']."/vsadmin/db_conn_open.php";
		
	global $ECTWPDB;
	if(!$ECTWPDB)
	{
		$ECTWPDB=new wpdb($db_username, $db_password, $db_name, $db_host);
	}
	if($ECTWPDB)
	{
		$ProdArr=$ECTWPDB->get_results("select pID,pName from products where pDisplay=1 and pInStock>0 order by pName");
		if(!empty($ProdArr))
			return $ProdArr;
	}
}

function DataToArray($NoC='')
{
	global $wpdb;
	$FinalArr='';
	$ExcludedPages=get_option('ect_sitemap_excluded_pages');
	$ExcludedPosts=get_option('ect_sitemap_excluded_posts');
	
	$ExQ='';
	if(empty($NoC) && !empty($ExcludedPages) && is_array($ExcludedPages))
		$ExQ='and ID NOT IN ('.implode(',',$ExcludedPages);
	
	if(empty($NoC) && !empty($ExcludedPosts) && is_array($ExcludedPosts))
		$ExQ.=','.implode(',',$ExcludedPosts).')';

	$Data=$wpdb->get_results("select ID,post_modified_gmt,post_title from ".$wpdb->prefix."posts where post_status='publish' $ExQ");
	if(!empty($Data))
	{
		$i=0;
		foreach($Data as $Ar)
		{
			if(!empty($Ar->post_title))
			{
				$ModifyDate = explode(' ',$Ar->post_modified_gmt);
				$ModifyDate = $ModifyDate[0];
				$Url=esc_url(get_permalink($Ar->ID));
				$SubArr='page';
	
			if($Url==get_home_url().'/')	
					$SubArr='home';
				elseif(get_post_type($Ar->ID)=='post')
					$SubArr='post';
				$FinalArr[$SubArr][$i]['Url']=$Url;
				$FinalArr[$SubArr][$i]['LastModify']=$ModifyDate;
				$FinalArr[$SubArr][$i]['PID']=$Ar->ID;
				$FinalArr[$SubArr][$i]['PostTitle']=ucwords($Ar->post_title);
				$i++;
			}
		}
	}
	if(is_array($FinalArr))
		ksort($FinalArr);
	return $FinalArr;
}
function StorePages()
{ 
	$LastM=mktime(date('H'),date('i'),date('s'),date('m'),date('d')-1,date('Y'));
	$LastM=date('Y-m-d',$LastM);
	return $Data=array('Search'=>array('Url'=>site_url('/').'search.php','PostTitle'=>'Search','LastModify'=>$LastM),'Cart'=>array('Url'=>site_url('/').'cart.php','PostTitle'=>'Cart','LastModify'=>$LastM),'Products'=>array('Url'=>site_url('/').'products.php','PostTitle'=>'Products','LastModify'=>$LastM),'Categories'=>array('Url'=>site_url('/').'categories.php','PostTitle'=>'Categories','LastModify'=>$LastM));
}
function GetCats($Xml='')
{
	global $db_username,$db_password,$db_name,$db_host;
	if($Xml)
		include $_SERVER['DOCUMENT_ROOT']."/vsadmin/db_conn_open.php";
	else	
		include "vsadmin/db_conn_open.php";
	global $ECTWPDB;
	if(!$ECTWPDB)
	{
		$ECTWPDB=new wpdb($db_username, $db_password, $db_name, $db_host);
	}
	if($ECTWPDB)
	{
		$CatsArr=$ECTWPDB->get_results("SELECT sectionID,sectionName AS sectionName,sectionDescription AS sectionDescription,sectionImage,sectionOrder,rootSection,sectionurl AS sectionurl FROM sections WHERE topSection=0 AND sectionDisabled<=0 ORDER BY sectionName");
		if(!empty($CatsArr))
		{	
			foreach($CatsArr as $Pp)
			{
				$CatName=$Pp->sectionID;
				if($GLOBALS['usecategoryname'])
					$CatName=$Pp->sectionName;
				
				$k='products.php';
					if($Pp->rootSection==0)
						$k='categories.php';
				if(!$Xml)
				{
					
						
					$Tmp.='<li><a title="'.$Pp->sectionName.'" href="'.UM('category/',str_replace(' ','-',$Pp->sectionName),$k.'?cat='.urlencode($CatName)).'">'.$Pp->sectionName.'</a>';
					$PC=ProdByCat($Pp->sectionID);
					if(!empty($PC))
					{
						$Tmp.='<ul class="ect_sm_sub_sub">';
						$Tmp.=ProdByCat($Pp->sectionID);	
						$Tmp.='</li></ul>';	
					}
				}
				else
				{
					$Tmp.='<url><loc>'.UM('category/',str_replace(' ','-',$Pp->sectionName),$k.'?cat='.urlencode ($CatName)).'</loc></url>';
					$PC=ProdByCat($Pp->sectionID,1);
					if(!empty($PC))
						$Tmp.=ProdByCat($Pp->sectionID,1);	
				}
			}
		}
		return $Tmp;
	}	
}
function ProdByCat($CatID,$Xml='')
{
	$Tmp='';
	global $db_username,$db_password,$db_name,$db_host;
	if($Xml)
		include $_SERVER['DOCUMENT_ROOT']."/vsadmin/db_conn_open.php";
	else
		include "vsadmin/db_conn_open.php";
		
	global $ECTWPDB;
	if(!$ECTWPDB)
	{
		$ECTWPDB=new wpdb($db_username, $db_password, $db_name, $db_host);
	}
	if($ECTWPDB)
	{
$CatsArr1=$ECTWPDB->get_results("SELECT sectionID,sectionName AS sectionName,sectionDescription AS sectionDescription,sectionImage,sectionOrder,rootSection,sectionurl AS sectionurl FROM sections WHERE topSection=$CatID AND sectionDisabled<=0 ORDER BY sectionName");
		if(!empty($CatsArr1))
		{	
			foreach($CatsArr1 as $Pp1)
			{
				$CatName=$Pp1->sectionID;
				if($GLOBALS['usecategoryname'])
					$CatName=$Pp1->sectionName;
					
				if(!$Xml)
				{
					$Tmp.='<li><a title="'.$Pp1->sectionName.'" href="'.UM('product/',str_replace(' ','-',$Pp1->sectionName),'products.php?cat='.urlencode($CatName)).'">'.$Pp1->sectionName.'</a>';
					$PC=ListStoreProd($Pp1->sectionID);
					if(!empty($PC))
					{
						$Tmp.='<ul class="ect_sm_sub_sub_sub">';
						$Tmp.=ListStoreProd($Pp1->sectionID);	
						$Tmp.='</li></ul>';	
					}
				}
				else
					$Tmp.='<url><loc>'.UM('product/',str_replace(' ','-',$Pp1->sectionName),'products.php?cat='.urlencode($CatName)).'</loc></url>';
			}
		}
		return $Tmp;
	}	
} 
function UM($P,$Sec,$Cust='')
{
	$IsSeo=$GLOBALS['seodetailurls'];
	
	$Url=site_url('/');
	if($IsSeo)
		$Url.=$P.$Sec;
	else
		$Url.=$Cust;
	return $Url;
}
?>