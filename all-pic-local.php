<?php
/*
Plugin Name: All-Pic-Local
Plugin URI: http://huiyi.in/title/APL.html
Description: 作者ccaiai
Author: ccaiai
Version: 0.1
Author URI: http://huiyi.in/
*/
//----------------------------注册AACTION-----------------------------//
add_action('admin_menu', 'apl_menu');//后台动作
//---------------------------增加设置页面-----------------------------//
function apl_menu() {
    add_options_page('图片本地化', '下载图片到本地', 7, 'APL_options', 'APL_options');
}
//-----------------------------设置页面-----------------------------//
function apl_options() {
?>
    <div class="wrap">
            <div style="float: left;"><h2>ALL Pic Local 0.2 设置</h2>
            </div>       
            <div style="float: right;"><h3>获取帮助请移步<a href="http://huiyi.in/title/APL.html">回忆里</a></h3></div>
        <div style="clear: both;"></div>        
<?php
    if(isset($_POST['apl_option'])){//是否有设置post信息,有则更新设置
        //update_option('apl_option_sql_loc',$_POST['sql_loc']);
        //update_option('apl_option_sql_name',$_POST['sql_name']);
        //update_option('apl_option_sql_pass',$_POST['sql_pass']);
        update_option('apl_option_save_path',$_POST['save_path']);
        if(isset($_POST['path_time']))update_option('apl_option_path_time','yes');
        else update_option('apl_option_path_time','not');
        update_option('apl_option_curl_check',$_POST['curl_check']);
        update_option('apl_option_time_check',$_POST['time_check']);
        echo "<div aline='center' style='padding:10px ;margin: 10px 300px 10px 300px;border: 1px solid #4D9214;box-shadow: #D0D0D0 0 0 6px;border-radius: 5px;'>
        <span style='color:red;'>本地</span>设置已更新</div>";
    } 
if(isset($_POST['APL_To_Get_Pic_Url'])){
    $PIDS  = $_POST["pid"];
    //echo $PIDS;
    global $post;
    $newABSPATH = str_replace("\\","/",ABSPATH);  //右斜杠替换为左斜杠
    $siteurl = get_option('siteurl'); //博客网址
    if (substr($siteurl, -1) != "/") $siteurl = $siteurl."/";//保证左斜杠结尾
    $newABSPATH=str_replace("\\","/",ABSPATH);
    $save_path_before="wp-content/uploads/".get_option('apl_option_save_path');//保存地址
    //$sql_loc=get_option('apl_option_sql_loc');
    //$sql_name=get_option('apl_option_sql_name');
    //$sql_pass=get_option('apl_option_sql_pass');
    $not_local_pic_num=0;//非本地图片计数
    $yes_local_pic_num=0;//本地图片计数
    $yes_read_pic_num=0;//下载成功图片计数
    $not_read_pic_num=0;//下载失败图片计数
    $yes_write_pic_num=0;//写入成功计数
    $not_write_pic_num=0;//写入失败计数
    $yes_change_pic_num=0;//替换图片成功计数
    $not_change_pic_num=0;//替换图片失败计数
    $not_download_pic_num_path=0;//由于文件名失败计数
//------------------------------基本参数完毕--------------------//
    if($PIDS!=NULL){
    foreach($PIDS as $post_id){//遍历数组中的文章ID
        if($post_id==NULL)continue;
        $post=get_post($post_id);
        //var_dump($post);
        $not_ok_pic=null;//将储存url的数组初始化
        $post_date=$post->post_date;//获取日志时间，为下一步建立时间文件夹
        $post_date=strtotime($post_date);//格式化时间
        if(get_option('apl_option_path_time')=='yes'){
            $save_path = $save_path_before.date("Y",$post_date)."/".date("m",$post_date)."/";//图片保存的路径目录
        }
        //echo $save_path;
        $imageContent = $post->post_content;//文章内容
        $imagePattern = '~<img [^\>]*\ />~';//匹配一般
        preg_match_all($imagePattern,$imageContent,$aPics); 
        $iNumberOfPics = count($aPics[0]); // 检查一下至少有一张图片 
        ?><div class="wrap"><?
        if ($iNumberOfPics < 1) { //另一种方式扫描
            preg_match_all('/<img.+src=\"?(.+\.(jpg|gif|bmp|jpeg|png|JPG|GIF|BMP|JPEG))\"?.+>/i',$imageContent,$aPics);
            $iNumberOfPics = count($aPics[0]); // 再次检查一下至少有一张图片 
        }
        if($iNumberOfPics>0){
            for($i=0;$i<$iNumberOfPics;$i++){ 
                $thumbnail = $aPics[0][$i];
                //提取url地址
                $len0 = strpos($thumbnail, 'src=');
                $len1 = strpos($thumbnail, 'http', $len0);
                if ($len1 === false) continue;
                $len2 = stripos($thumbnail, '.jpeg', $len1);
                if ($len2 === false) {
                    $len2 = stripos($thumbnail, '.jpg', $len1);
                    if ($len2 === false) {
                        $len2 = stripos($thumbnail, '.png', $len1);
                        if ($len2 === false) {
                            $len2 = stripos($thumbnail, '.bmp', $len1);
                            if ($len2 === false) {
                                $len2 = stripos($thumbnail, '.gif', $len1);
                                if ($len2 === false) {
                                    continue;
                                }
                            }
                        }
                    }
                $pic_url=substr($thumbnail,$len1,$len2-$len1+4);
                $local_or_not = stripos($pic_url, "$siteurl");//判断图片链接中是否有本地路径
                //echo $pic_url.$siteurl.$local_or_not;
                if ($local_or_not === false) {$not_local_pic_num++;}else{$yes_local_pic_num++;continue;}//判断是否为本地图片
                $dest_pic_name = $newABSPATH.$save_path.basename($pic_url);
                if(file_exists($dest_pic_name)){$info=pathinfo($dest_pic_name);$dest_pic_name=dirname($dest_pic_name).'/9'.$info['filename']."_".time().'.'.$info['extension'];}
                //set_time_limit(180);
                if(get_option('apl_option_curl_check')==yes){
                if(!check_remote_file_apl($pic_url)){
                    $not_read_pic_num++;
                    $not_ok_pic["$not_read_pic_num"]=$pic_url;
                    $not_ok_id["$post_id"]=$not_ok_pic;
                    continue;
                }}
                $read_file=@fopen($pic_url,"r");
                if ($read_file) {
                    $yes_read_pic_num++;//成功从远程读取
                    if(!is_dir($newABSPATH.$save_path))mkdirs_apl($newABSPATH.$save_path,0777);
                    $write_file = fopen ($dest_pic_name, "wb");
                    if($write_file){
                        while(!feof($read_file)) {
                            fwrite($write_file, fread($read_file, 1024 * 8 ), 1024 * 8 );
                        }
                    }
                }
                else{$not_read_pic_num++;
                    $not_read_pic_num++;
                    $not_ok_pic["$not_read_pic_num"]=$pic_url;
                    $not_ok_id["$post_id"]=$not_ok_pic;
					@fclose($read_file);
					continue;
				}//没有下载下来
                if($read_file){
                    fclose($read_file);
                }
                if($write_file){
                    fclose($write_file);
                    $yes_write_pic_num++;
                }
                $pic_url_new=$siteurl.$save_path.basename($pic_url);
                /*if (!$apl_link=mysql_connect($sql_loc,$sql_name,$sql_pass))continue;
                $changed_pic_or_not=mysql_query("UPDATE wp_posts SET post_content = replace(post_content,'$pic_url','$pic_url_new')");*/
                global $wpdb;
                $changed_pic_or_not = $wpdb->query("UPDATE $wpdb->posts SET post_content = replace(post_content,'$pic_url','$pic_url_new')");
                if($changed_pic_or_not==TURE){$yes_change_pic_num++;}else{$not_change_pic_num++;continue;}
                }
            }
        }
    }
    echo "<div aline='center' style='padding:10px ;margin: 10px;border: 1px solid #4D9214;box-shadow: #D0D0D0 0 0 6px;border-radius: 5px;'>久等了~蜗牛机器处理完成！<hr>
    1.发现:→发现非本地图片<span style='color:red;'>".$not_local_pic_num."</span>张，本地<span style='color:red;'>".$yes_local_pic_num."</span>张。<br/>
    <br/>
    2.读取:→读取成功<span style='color:red;'>".$yes_read_pic_num."</span>张，失败<span style='color:red;'>".$not_read_pic_num."</span>张。";
    if($not_read_pic_num>0)echo '失败原因可能是文件不存在，或获取超时。';
    echo "<br/><br/>
    3.写入:→写入成功<span style='color:red;'>".$yes_write_pic_num."</span>张，失败<span style='color:red;'>".$not_write_pic_num."</span>张。";
    if($not_write_pic_num>0)echo '原因可能是保存路径出错。';
    echo "<br/><br/>
    4.替换:→替换成功<span style='color:red;'>".$yes_change_pic_num."</span>张，失败<span style='color:red;'>".$not_change_pic_num."</span>张。";
    if($not_change_pic_num>0)echo '原因可能是数据库信息出错。';
    echo "<br/><br/>";
    echo "x.结果:→根据某C薄弱的加法，共成功<span style='color:red;'>".$yes_change_pic_num."</span>张图片。失败<span style='color:red;'>".($not_change_pic_num+$not_read_pic_num+$not_write_pic_num)."</span>张图片    ";
    echo "<br />失败的文章链接如下：";              
    if($not_ok_id!=null){
        foreach($not_ok_id as $key=>$value){
            $post=get_post($key);
            $title=$post->post_title;
            $guid=$post->guid;
            echo '<hr><a href='.$guid.'>'.$title.'</a><br/>';
            foreach($value as $value ){
                echo '<br/><a href='.$value.'>'.$value.'</a>';
            }
        }
     }
     echo "</div>";     
    } 
}
?>
    <form method="post">
    <fieldset class="options" style="border: 1px solid;margin-top: 1em;padding: 1em;-moz-border-radius: 8px;-khtml-border-radius: 8px;border-radius: 8px;">
        <legend><h3>保存路径</h3></legend>
        <div style="padding:15px 0 5px 15px;">
            <label for="save_path">图片保存地址:
                <?php bloginfo('url') ?>/wp-content/uploads/<input type="text" name="save_path"  id="save_path" value="<?php echo get_option('apl_option_save_path') ?>"/>&nbsp;(建议留空，若填写请以"/"结尾。)
            </label>
        </div>
        <div style="clear:both"></div>
        <div style="padding:5px 0 15px 40px ">
            <label for="path_time">
                <input type="checkbox" name="path_time"  id="path_time"  value="yes" <?php if(get_option('apl_option_path_time')==yes)echo 'checked="checked"'; ?>/>以文章日期建立文件夹 “/2011/07/"
            </label>
        </div>
    <div>
    </fieldset>  
        <fieldset class="options" style=" float:left;width:700px;border: 1px solid;margin-top: 1em;padding: 1em;-moz-border-radius: 8px;-khtml-border-radius: 8px;border-radius: 8px;">
        <legend><h3>下载前探测</h3></legend>
        <div style="padding:15px 0 5px 15px">
            <label for="curl_check1">
                <input type="radio" name="curl_check"  id="curl_check1"  value="1" <?php if(get_option('apl_option_curl_check')==1)echo 'checked="checked"'; ?>/>使用Curl判断文件是否存在，您的主机<strong><?php if(!function_exists('curl_init'))echo'貌似不'?>支持</strong>Curl。
            </label>
        </div>
        <div style="padding:5px 0 5px 15px">
            <label for="curl_check2">
                <input type="radio" name="curl_check"  id="curl_check2"  value="2" <?php if(get_option('apl_option_curl_check')==2)echo 'checked="checked"'; ?>/>使用Get_headers判断文件是否存在，您的主机<strong><?php if(!function_exists('get_headers'))echo'貌似不'?>支持</strong>Get_headers。
            </label>
        </div>
        <div style="padding:5px 0 15px 15px ">
            <label for="curl_check3">
                <input type="radio" name="curl_check"  id="curl_check3"  value="3" <?php if(get_option('apl_option_curl_check')==3)echo 'checked="checked"'; ?>/>不判断文件是否存在，速度快，但是如果原链接失效，下载下来会是空白图片。
            </label>
        </div>
      
         <div style="padding:5px 0 15px 15px ">
            <label for="time_check">
                <input type="text" name="time_check"  id="time_check" <?php if(($check_time=get_option('apl_option_time_check'))!="") echo 'value="'.$check_time.'"'?>" />探测超时，单位为秒。
            </label>
        </div>
     </div>
     <div style="float:right;border: 1px solid;margin:1em;margin-top:1.5em;;padding: 1em;-moz-border-radius: 8px;-khtml-border-radius: 8px;border-radius: 8px;">
     <iframe src="http://huiyi.in/c/apl.htm" width="330px" height="160px"></iframe>
     </div>
        <div style="clear:both;"></div>
        
     
        
        
        
      </fieldset>
        
        <div style="display:hidden;">
            <input type="hidden" name="apl_option" id="apl_option" value="apl_opintion"/>
        </div>
        
        <div style="padding:15px">
            <input type="submit" name="submit" value="更新设置" />
        </div>
        
    </form>
    <hr/>
    <h2>选择要操作的文章:</h2>
    
    <div>
        <form method="post">
            <h3>建议不要一次选择太多文章。</h3>
            <fieldset class="options" style="border: 1px solid;margin-top: 1em;padding: 1em;-moz-border-radius: 8px;-khtml-border-radius: 8px;border-radius: 8px;">
        <legend>
            
        </legend><div style="padding: 7px;"><input type="submit" name="submit" value="下载图片" /></div>
            <?php 
            global $post,$current_post_id,$max_post_id,$min_post_id;
            $myposts = get_posts('numberposts=-1');
            $num_of_post=0;
            foreach($myposts as $post):
            $current_post_id=$post->ID;//获取
            $num_of_post++;
            ?>    
            
            <div style="padding: 4px 0 4px 0;margin:2px 0 2px 5px;">
            <label for="<?php the_id() ?>">
            <div style="padding: 0 5px 0 5px;float:left;">
                <div style="padding: 0 5px 0 5px;float:left;margin-right: 5px;: ;"><?php echo $num_of_post;?></div>
                <input type="checkbox" name="pid[]" id="<?php the_id() ?>" value="<?php the_id() ?>"/></div>
                <div align="left"style="float:left;padding:0 5px 0 5px ;"><div style="font-size:14px;float:left;"><?php the_title(); ?></div>&nbsp;&nbsp;<a href="<?php the_permalink(); ?>">查看</a></div>  
            </label>
                <div style="clear:both;"></div> <?php if(($num_of_post%10)==0)echo '<hr>';?>
            </div>
            <input type="hidden" name="APL_To_Get_Pic_Url" value="go">
            <?php endforeach;?>
            <div style="padding: 7px;"><input type="submit" name="submit" value="下载图片" /></div>
        </fieldset>
        </form>
        </div> 
    </div>
<?php
} 
//-----------------------------声明需要的函数--------------------------//

if(!function_exists('check_remote_file_apl')){
function check_remote_file_apl(){
    $time_apl=get_option('apl_option_time_check');
    set_time_limit($time_apl);//设置超时时间
    $result=TRUE;
    if(get_option('apl_option_curl_check')==2){
        $Headers = get_headers($url);
        if(!preg_match("|200|", $Headers[0])) $result = FALSE;
    }
    if(get_option('apl_option_curl_check')==1){
        $curl = curl_init($url);
        // 不取回数据
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET'); //不加这个会返回403，加了才返回正确的200，原因不明
        // 发送请求
        $result = curl_exec($curl);
        $found = FALSE;
        // 如果请求没有发送失败
        if ($result !== FALSE){
            // 再检查http响应码是否为200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
            if($statusCode == 200){
                $result = TRUE;   
            }
        }
        curl_close($curl);
    }
return $result;
}
}

if(!function_exists('check_remote_file_exists')){
    function check_remote_file_exists($url) 
    {
        $curl = curl_init($url);
        // 不取回数据
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET'); //不加这个会返回403，加了才返回正确的200，原因不明
        // 发送请求
        $result = curl_exec($curl);
        $found = false;
        // 如果请求没有发送失败
        if ($result !== false) {
            // 再检查http响应码是否为200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  
            if ($statusCode == 200){
                $found = true;   
            }
        }
        curl_close($curl);
     
        return $found;
    }
}
    function mkdirs_apl($dir){
        if(!is_dir($dir)){
            mkdirs_apl(dirname($dir));
            mkdir($dir);
        }
    }
?>