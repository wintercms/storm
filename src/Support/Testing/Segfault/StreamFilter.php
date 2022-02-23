<?php namespace Winter\Storm\Support\Testing\Segfault;

/**
 * Segfault StreamFilter class
 *
 * Handles registering the StreamFilter that will
 * add the ticks=1 definition to loaded PHP files
 *
 * @see https://gist.github.com/lyrixx/56dfc48fb7e807dd2a229813da89a0dc#hardcore-debug-logger
 */
class StreamFilter extends \php_user_filter
{
    const NAME = 'winter-segfault-streamfilter';

    protected $buffer = '';

    public static function append($resource, string $path)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        stream_filter_append(
            $resource,
            static::NAME,
            STREAM_FILTER_READ,
            [
                'ext' => $ext,
                'path' => $path,
            ]
        );
    }

    public static function register()
    {
        stream_filter_register(static::NAME, static::class);
    }

    protected $_data = '';
    protected $bucket;

    public function filter($in, $out, &$consumed, $closing)
    {
        if ($this->params['ext'] !== 'php') {
            // return PSFS_PASS_ON;
        }


        /* We read all the stream data and store it in
           the '$_data' variable
        */
        while($bucket = stream_bucket_make_writeable($in))
        {
            $this->_data .= $bucket->data;
            $this->bucket = $bucket;
            $consumed = 0;
        }

        /* Now that we have read all the data from the stream we process
          it and save it again to the bucket.
        */
        if ($closing) {
            $consumed += strlen($this->_data);

            $buffer = $this->_data;
            if (strpos($buffer, "<?php") === 0) {
                $buffer = str_replace("<?php", "<?php\ndeclare(ticks=1);\n", $buffer);
            }

            $this->bucket->data = $buffer;
            $this->bucket->datalen = strlen($buffer);

            // die($this->bucket->datalen . '-' . strlen($buffer) . '-' . strlen("\ndeclare(ticks=1);\n"));

            if(!empty($this->bucket->data)) {
                stream_bucket_append($out, $this->bucket);
            }

            // die(var_dump(stream_bucket_make_writeable($out)->data));

            return PSFS_PASS_ON;
        }

        return PSFS_FEED_ME;




        // $buffer = '';
        // $bytes = 0;


        // while ($bucket = stream_bucket_make_writeable($in)) {
        //     $buffer .= $bucket->data;
        //     $bytes += $bucket->datalen;
        //     if (
        //         $bytes >= strlen('<?php')
        //         && strpos($buffer, '<?php', 0) === 0
        //     ) {

        //         $output = $bytes . $buffer;


        //     }

        //     $consumed += $bucket->datalen;
        //     stream_bucket_append($out, $bucket);
        // }

        // die($output);



        // // while ($bucket = stream_bucket_make_writeable($in)) {
        // //     $this->buffer .= $bucket->data;
        // //     $consumed += $bucket->datalen;
        // // }
        // // if ($closing) {
        // //     // die(var_dump($consumed));
        // //     $buffer = $this->doFilter($this->buffer, $this->params['path'], $this->params['ext']);
        // //     $bucket = stream_bucket_new($this->stream, $buffer);
        // //     stream_bucket_append($out, $bucket);

        // //     die(var_dump($consumed) . "\n\n\n\n" . var_dump($out));
        // // }

        // return PSFS_PASS_ON;
    }

    protected function doFilter($buffer, $path, $ext): string
    {
        if ('php' !== $ext) {
            return $buffer;
        }

        if (0 !== strpos($buffer, "<?php")) {
            return $buffer;
        }

        // die($buffer);

        $buffer = str_replace("<?php", "<?php\ndeclare(ticks=1);\n", $buffer);

        return $buffer;
    }
}




// class FileSkip32Bytes extends php_user_filter
// {
//    private $skipped=0;



//    function filter($in, $out, &$consumed, $closing)  {




//     while ($bucket = stream_bucket_make_writeable($in)) {
//         // Get hte current bucket's length
//         $outlen = $bucket->datalen;

//         // Check if we've skipped 32 bytes yet
//         if ($this->skipped < 32){
//             // We haven't, so get the lower number of either the current bucket length
//             // or the remaining bytes required to skip the first 32
//             $outlen = min($bucket->datalen,32-$this->skipped);

//             // Chop off the corresponding number of bytes from the input data
//             $bucket->data = substr($bucket->data, $outlen);

//             // Ensure that the bucket knows that it has lost those bytes
//             $bucket->datalen = $bucket->datalen-$outlen;

//             // Remember how many bytes we have skipped so far
//             $this->skipped+=$outlen;
//         }
//          $consumed += $outlen;
//          stream_bucket_append($out, $bucket);
//       }
//       return PSFS_PASS_ON;
//    }
// }