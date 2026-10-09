<?php
// Run against the disposable real WordPress installation with WP_DEBUG enabled.
require rtrim($argv[1], '/\\').'/wp-load.php';
set_error_handler(static function($severity,$message,$file,$line) {
    if (error_reporting() & $severity) throw new ErrorException($message,0,$severity,$file,$line);
    return false;
});
function review_check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
$valid=['label'=>'Checked period','start'=>['year'=>2028,'month'=>2,'day'=>29],'finish'=>['year'=>2028,'month'=>3,'day'=>2]];
review_check(str_contains(Orthocal_Plugin::periods([$valid]),'Checked period'),'Valid leap-year period lost');
$bad=[];
foreach (['start','finish'] as $side) {
    foreach (['year','month','day'] as $part) {
        $item=$valid;unset($item[$side][$part]);$bad[]=$item;
        $item=$valid;$item[$side][$part]=[];$bad[]=$item;
    }
    foreach ([null,'invalid',['year'=>2027,'month'=>2,'day'=>29],['year'=>2028,'month'=>13,'day'=>1],['year'=>2028,'month'=>2,'day'=>30]] as $date) {
        $item=$valid;$item[$side]=$date;$bad[]=$item;
    }
}
$bad[]=null;$bad[]='invalid';$item=$valid;$item['label']=[];$bad[]=$item;
$item=$valid;$item['start']=$valid['finish'];$item['finish']=$valid['start'];$bad[]=$item;
review_check(Orthocal_Plugin::periods($bad)==='','Malformed periods were not skipped');
review_check(Orthocal_Plugin::periods(null)==='','Invalid period collection was not skipped');
review_check(str_contains(Orthocal_Plugin::periods(array_merge($bad,[$valid])),'Checked period'),'Malformed periods suppressed valid periods');
review_check(isset(Orthocal_Plugin::options()['public_api_url']),'Existing settings unavailable');
review_check(shortcode_exists('orthocal_today'),'Existing shortcode unavailable');
$saved_get=$_GET;$saved_server=$_SERVER;
foreach (['2028-02-29'=>'2028-02-29','2027-02-29'=>'','2028-13-01'=>'','bad'=>''] as $input=>$expected) {
    $_GET['orthocal_date']=$input;
    review_check(Orthocal_Plugin::requested_date()===$expected,'Public date validation failed');
}
$_GET['orthocal_date']=[];
review_check(Orthocal_Plugin::requested_date()==='','Array date accepted');
unset($_GET['orthocal_date']);
review_check(Orthocal_Plugin::requested_date()==='','Missing date accepted');
foreach (['192.0.2.10'=>'192.0.2.10','2001:db8::1'=>'2001:db8::1','invalid'=>'','192.0.2.10, 192.0.2.11'=>''] as $input=>$expected) {
    $_SERVER['REMOTE_ADDR']=$input;
    review_check(Orthocal_Plugin::client_ip()===$expected,'Client IP validation failed');
}
$_SERVER['REMOTE_ADDR']=[];
review_check(Orthocal_Plugin::client_ip()==='','Array client IP accepted');
unset($_SERVER['REMOTE_ADDR']);
review_check(Orthocal_Plugin::client_ip()==='','Missing client IP accepted');
$_GET=$saved_get;$_SERVER=$saved_server;
Orthocal_Plugin::throttle();Orthocal_Plugin::media_throttle();
global $wpdb;
$keys=$wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_orthocal_%'");
review_check(count(array_filter($keys,static fn($key)=>str_starts_with($key,'_transient_orthocal_rate_')))>0,'Prefixed rate counter not stored');
review_check(count(array_filter($keys,static fn($key)=>str_starts_with($key,'_transient_orthocal_media_rate_')))>0,'Prefixed media counter not stored');
restore_error_handler();
echo "PASS review: malformed API periods without warnings, valid dates, compatible settings/shortcodes and prefixed rate counters\n";
