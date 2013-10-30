<?php
namespace MarkdownFilter;

use \Michelf\MarkdownExtra as MarkdownExtra;

class MarkdownFilter extends \php_user_filter
{
    
    private $bufferHandle = '';
    
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

        $parser = new MarkdownExtra;
        $html = $parser->transform($data);
        $buck->data = $html;
        
        stream_bucket_append($out, $buck);
        return PSFS_PASS_ON;
    }
    
    public function onCreate()
    {
        $this->bufferHandle = @fopen('php://temp', 'w+');
        if (false !== $this->bufferHandle) {
            return true;
        }
        return false;
    }
    
    public function onClose()
    {
        @fclose($this->bufferHandle);
    }
}
