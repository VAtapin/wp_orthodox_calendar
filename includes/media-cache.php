<?php
if (!defined('ABSPATH')) exit;

/** Public calendar media only. No arbitrary remote URLs, credentials or PHP uploads. */
final class Orthocal_Media_Cache {
    const ORIGIN = 'https://kalender.georg-kloster.ru';
    const BIBLE_DESKTOP_ORIGIN = 'https://bible-desktop.com';
    const MAX_BYTES = 200 * 1024 * 1024;
    private static $started;
    static function boot() { add_action('orthocal_refresh_asset',[self::class,'refresh'],10,1); }
    static function source($path) {
        if (!is_string($path)) return false;
        if (str_starts_with($path,self::ORIGIN.'/')) $path=substr($path,strlen(self::ORIGIN));
        if (preg_match('#^https://bible-desktop\.com/storage/calendar-icons/[a-f0-9]{64}\.(?:png|svg|webp|jpg|jpeg|gif)$#D',$path)) return $path;
        if (preg_match('#^https://bible-desktop\.com/api/calendar/icons/[0-9]+/images/[0-9]+$#D',$path)) return $path;
        if ($path==='/calendar-api-font.php') return $path;
        if (!preg_match('~^/assets/(?:markers|typikon|icons)/[a-zA-Z0-9_/-]+\.(?:png|svg|webp|jpg|jpeg|gif)$~D',$path) || str_contains($path,'//')) return false;
        return $path;
    }
    static function directory() {
        $upload=wp_upload_dir();
        if ($upload['error']) return false;
        $dir=$upload['basedir'].'/orthocal-cache';
        if (is_link($dir) || (!is_dir($dir) && !wp_mkdir_p($dir))) return false;
        if (!is_file($dir.'/index.html')) @file_put_contents($dir.'/index.html','');
        return ['path'=>$dir,'url'=>$upload['baseurl'].'/orthocal-cache'];
    }
    static function metadata($path) { return get_option('orthocal_media_'.hash('sha256',$path),[]); }
    static function file_valid($name) { return is_string($name) && preg_match('/^[a-f0-9]{64}\.(png|svg|webp|jpg|jpeg|gif|ttf)$/D',$name); }
    static function local($meta,$dir) {
        return !empty($meta['file']) && self::file_valid($meta['file']) && is_file($dir['path'].'/'.$meta['file']) && !is_link($dir['path'].'/'.$meta['file']) ? $dir['url'].'/'.$meta['file'] : '';
    }
    static function cached_url($source) {
        $path=self::source($source);if(!$path)return '';
        $dir=self::directory();if(!$dir)return '';
        return self::local(self::metadata($path),$dir);
    }
    static function thumbnail_url($source) {
        $original=self::url($source);if(!$original)return '';
        $dir=self::directory();if(!$dir)return '';
        $name=basename((string)parse_url($original,PHP_URL_PATH));
        if(!self::file_valid($name))return '';
        // SVG is already vector-small at the requested CSS size. Raster files
        // get a separate 96px WebP/JPEG derivative for gallery previews.
        if(pathinfo($name,PATHINFO_EXTENSION)==='svg'||!function_exists('imagecreatefromstring'))return $original;
        $thumbName=hash('sha256',$name.'|orthocal-thumbnail-96').'.'.(function_exists('imagewebp')?'webp':'jpg');
        $thumb=$dir['path'].'/'.$thumbName;if(is_file($thumb)&&!is_link($thumb))return $dir['url'].'/'.$thumbName;
        $body=@file_get_contents($dir['path'].'/'.$name);$image=$body===false?false:@imagecreatefromstring($body);
        if(!$image)return $original;
        try {
            $width=imagesx($image);$height=imagesy($image);if($width<1||$height<1)return $original;
            $scale=min(1,96/max($width,$height));$targetWidth=max(1,(int)round($width*$scale));$targetHeight=max(1,(int)round($height*$scale));
            $target=imagecreatetruecolor($targetWidth,$targetHeight);imagealphablending($target,false);imagesavealpha($target,true);imagefill($target,0,0,imagecolorallocatealpha($target,0,0,0,127));
            imagecopyresampled($target,$image,0,0,0,0,$targetWidth,$targetHeight,$width,$height);
            $tmp=$dir['path'].'/'.hash('sha256',$thumbName).'.tmp';$written=function_exists('imagewebp')?imagewebp($target,$tmp,72):imagejpeg($target,$tmp,76);imagedestroy($target);
            if(!$written||!is_file($tmp)||!rename($tmp,$thumb)){@unlink($tmp);return $original;}
            return $dir['url'].'/'.$thumbName;
        } finally {imagedestroy($image);}
    }
    static function schedule($path) {
        if (!wp_next_scheduled('orthocal_refresh_asset',[$path])) wp_schedule_single_event(time()+5,'orthocal_refresh_asset',[$path]);
    }
    static function url($source) {
        $path=self::source($source); if (!$path) return '';
        $dir=self::directory(); if (!$dir) { update_option('orthocal_media_error','Каталог uploads недоступен для записи.',false); return ''; }
        $meta=self::metadata($path); $url=self::local($meta,$dir);
        $hours=(int)Orthocal_Plugin::options()['media_hours'];
        if ($url) {
            if (($meta['checked']??0)+$hours*3600<time() && ($meta['retry']??0)<time()) self::schedule($path);
            return $url;
        }
        if (($meta['retry']??0)>time()) return '';
        self::$started ??= microtime(true);
        if (microtime(true)-self::$started>8) { self::schedule($path); return ''; }
        self::refresh($path);
        return self::local(self::metadata($path),$dir);
    }
    static function validate_body($body,$ext) {
        if (!is_string($body) || $body==='' || strlen($body)>10*1024*1024) return false;
        if ($ext==='ttf') return in_array(substr($body,0,4),["\x00\x01\x00\x00",'OTTO'],true) ? $body : false;
        if ($ext!=='svg') {
            $info=@getimagesizefromstring($body);
            $mime=['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp','gif'=>'image/gif'];
            return $info && ($info['mime']??'')===($mime[$ext]??'') && $info[0]*$info[1]<=24000000 ? $body : false;
        }
        if (!class_exists('DOMDocument') || preg_match('/<!DOCTYPE|<!ENTITY/i',$body)) return false;
        $previous=libxml_use_internal_errors(true); $doc=new DOMDocument();
        try { if (!$doc->loadXML($body,LIBXML_NONET) || $doc->documentElement?->localName!=='svg') return false; }
        finally { libxml_clear_errors();libxml_use_internal_errors($previous); }
        $allowed=['svg','g','path','circle','ellipse','rect','line','polyline','polygon','defs','linearGradient','radialGradient','stop','clipPath','mask','title','desc','use'];
        $attributes=['id','xmlns','viewBox','width','height','x','y','x1','y1','x2','y2','cx','cy','r','rx','ry','d','points','fill','fill-rule','fill-opacity','stroke','stroke-width','stroke-linecap','stroke-linejoin','stroke-miterlimit','stroke-dasharray','stroke-dashoffset','stroke-opacity','opacity','transform','gradientTransform','gradientUnits','offset','stop-color','stop-opacity','clip-path','clip-rule','mask','preserveAspectRatio','href'];
        // Expand the source's simple class rules into presentation attributes.
        // No CSS survives in the file; every resulting value is validated below.
        $rules=[];
        foreach($doc->getElementsByTagName('style') as $style) {
            preg_match_all('/([^{}]+)\{([^{}]*)\}/u',$style->textContent,$matches,PREG_SET_ORDER);
            foreach($matches as $match)foreach(explode(',',$match[1]) as $selector)if(preg_match('/^\.([\w-]+)$/u',trim($selector),$class))$rules[$class[1]]=($rules[$class[1]]??'').';'.$match[2];
        }
        foreach (iterator_to_array($doc->getElementsByTagName('*')) as $node) {
            if (!in_array($node->localName,$allowed,true)) { $node->parentNode?->removeChild($node); continue; }
            $declarations='';foreach(preg_split('/\s+/u',$node->getAttribute('class')) as $class)$declarations.=';'.($rules[$class]??'');
            $declarations.=';'.$node->getAttribute('style');
            foreach(explode(';',$declarations) as $declaration) {
                $pair=explode(':',$declaration,2);if(count($pair)!==2)continue;
                $property=trim($pair[0]);if(in_array($property,['fill','fill-rule','fill-opacity','stroke','stroke-width','stroke-linecap','stroke-linejoin','stroke-miterlimit','stroke-dasharray','stroke-dashoffset','stroke-opacity','opacity','stop-color','stop-opacity','clip-path','clip-rule'],true))$node->setAttribute($property,trim($pair[1]));
            }
            if($node->hasAttribute('xlink:href'))$node->setAttribute('href',$node->getAttribute('xlink:href'));
            foreach (iterator_to_array($node->attributes) as $attr) {
                $value=$attr->value;
                $safe=in_array($attr->nodeName,$attributes,true) && !preg_match('/[<>]|javascript:|data:|https?:|\\\\/i',$value);
                if ($attr->nodeName==='xmlns') $safe=$value==='http://www.w3.org/2000/svg';
                if ($attr->nodeName==='href') $safe=(bool)preg_match('/^#[\p{L}_][\p{L}\p{N}_-]*$/uD',$value);
                if (stripos($value,'url')!==false && !preg_match('/^url\(#[\p{L}_][\p{L}\p{N}_-]*\)$/uD',$value)) $safe=false;
                if (!$safe) $node->removeAttributeNode($attr);
            }
        }
        return $doc->saveXML($doc->documentElement);
    }
    static function refresh($source) {
        $path=self::source($source);$dir=self::directory(); if (!$path || !$dir) return false;
        // A single global lock also prevents concurrent purge/write races and cache stampedes.
        $lock=@fopen($dir['path'].'/cache.lock','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) { if($lock)fclose($lock);self::schedule($path);return false; }
        try {
            $meta=self::metadata($path);$headers=[];
            if (self::local($meta,$dir)) {
                if (!empty($meta['etag'])) $headers['If-None-Match']=$meta['etag'];
                if (!empty($meta['modified'])) $headers['If-Modified-Since']=$meta['modified'];
            }
            $remote=str_starts_with($path,'https://')?$path:self::ORIGIN.$path;
            $response=wp_safe_remote_get($remote,['timeout'=>5,'redirection'=>0,'headers'=>$headers,'limit_response_size'=>10*1024*1024+1]);
            $code=is_wp_error($response)?0:wp_remote_retrieve_response_code($response);
            $key='orthocal_media_'.hash('sha256',$path);
            if ($code===304 && self::local($meta,$dir)) {
                $meta['checked']=time();$meta['retry']=0;unset($meta['error']);update_option($key,$meta,false);return true;
            }
            $ext=$path==='/calendar-api-font.php'?'ttf':strtolower(pathinfo($path,PATHINFO_EXTENSION));
            if ($ext==='') {
                $mime=strtolower(trim(explode(';',(string)wp_remote_retrieve_header($response,'content-type'),2)[0]));
                $ext=['image/png'=>'png','image/svg+xml'=>'svg','image/webp'=>'webp','image/jpeg'=>'jpg','image/gif'=>'gif'][$mime]??'';
            }
            $body=$code===200?self::validate_body(wp_remote_retrieve_body($response),$ext):false;
            if ($body===false) {
                $meta=array_merge($meta,['source'=>$path,'retry'=>time()+900,'error'=>'Не удалось обновить файл (HTTP '.$code.').']);
                update_option($key,$meta,false);return false;
            }
            $name=hash('sha256',$body).'.'.$ext;
            if (!is_file($dir['path'].'/'.$name)) {
                $stats=self::stats();
                if ($stats['bytes']+strlen($body)>self::MAX_BYTES) {
                    update_option('orthocal_media_error','Кэш медиа достиг 200 МБ. Очистите его в разделе «Кэш и файлы».',false);return false;
                }
                $tmp=$dir['path'].'/'.hash('sha256',$path).'.tmp';
                if (file_put_contents($tmp,$body,LOCK_EX)!==strlen($body) || !rename($tmp,$dir['path'].'/'.$name)) return false;
            }
            update_option($key,['source'=>$path,'file'=>$name,'checked'=>time(),'retry'=>0,
                'etag'=>sanitize_text_field(wp_remote_retrieve_header($response,'etag')),
                'modified'=>sanitize_text_field(wp_remote_retrieve_header($response,'last-modified'))],false);
            delete_option('orthocal_media_error');
            return true;
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }
    static function entries() {
        global $wpdb;
        $names=$wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",$wpdb->esc_like('orthocal_media_').'%'));
        $entries=[];foreach($names as $name) if(preg_match('/^orthocal_media_[a-f0-9]{64}$/D',$name)) $entries[$name]=get_option($name,[]);
        return $entries;
    }
    static function stats() {
        $dir=self::directory();$count=0;$bytes=0;
        if($dir)foreach(glob($dir['path'].'/*')?:[] as $file) if(self::file_valid(basename($file)) && !is_link($file)){$count++;$bytes+=filesize($file);}
        return ['count'=>$count,'bytes'=>$bytes];
    }
    static function recheck() { foreach(self::entries() as $meta) if(!empty($meta['source'])) self::schedule($meta['source']); }
    static function clear() {
        $dir=self::directory();if(!$dir)return false;
        $lock=@fopen($dir['path'].'/cache.lock','c');if(!$lock)return false;
        if(!flock($lock,LOCK_EX|LOCK_NB)){fclose($lock);return false;}
        try {
            foreach(glob($dir['path'].'/*')?:[] as $file) if(self::file_valid(basename($file)) && !is_link($file)) unlink($file);
            foreach(self::entries() as $name=>$meta) {if(!empty($meta['source']))wp_clear_scheduled_hook('orthocal_refresh_asset',[$meta['source']]);delete_option($name);}
            delete_option('orthocal_media_error');return true;
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }
}
