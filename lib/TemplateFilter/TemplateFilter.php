<?php
namespace TemplateFilter;

use \Rain\Tpl as View;

class TemplateFilter extends \php_user_filter
{
    
    private $bufferHandle = '';
    private $docTitle = 'Untitled';
    
    public function filter($in, $out, &$consumed, $closing)
    {
        $data = '';

        while ($bucket = stream_bucket_make_writeable($in)) {
            $data .= $bucket->data;
            $consumed += $bucket->datalen;
        }

        $buck = stream_bucket_new($this->bufferHandle, '');
        
        if (false === $buck) {
            return PSFS_ERR_FATAL;
        }

        $config = array(
            "tpl_dir"       => dirname(__FILE__) . "/templates/",
            "cache_dir"     => sys_get_temp_dir() . "/",
            "auto_escape"   => false
        );
        View::configure($config);

        $view = new View();
        if (!$closing) {
            $matches = array();
            if (preg_match('/<h1>(.*)<\/h1>/i', $data, $matches)) {

                if (!empty($matches[1])) {
                    $this->docTitle = $matches[1];
                }
            }
            $view->assign('title', $this->docTitle);
            $view->assign('body', $data);
            $content = $view->draw('default', true);
            $buck->data = $content;
        }
        
        stream_bucket_append($out, $buck);
        return PSFS_PASS_ON;
    }
    
    public function onCreate()
    {
        $this->bufferHandle = @fopen('php://temp', 'w+');
        if (false !== $this->bufferHandle) {
            
            $info = explode('.', $this->filtername);
            if (is_array($info) && !empty($info[1])) {
                $this->docTitle = base64_decode($info[1]);
            }
            
            return true;
        }
        return false;
    }
    
    public function onClose()
    {
        @fclose($this->bufferHandle);
    }
}
