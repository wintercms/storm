<?php namespace Winter\Storm\Database\Attach;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Winter\Storm\Network\Http;
use Winter\Storm\Database\Model;
use Winter\Storm\Support\Facades\File as FileHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as FileObj;
use Winter\Storm\Exception\ApplicationException;

/**
 * File attachment model
 *
 * @author Alexey Bobkov, Samuel Georges
 *
 * @property string $file_name The name of the file
 * @property int $file_size The size of the file
 * @property string $content_type The MIME type of the file
 * @property string $disk_name The generated disk name of the file
 */
class File extends Model
{
    use \Winter\Storm\Database\Traits\Sortable;

    /**
     * @var string The table associated with the model.
     */
    protected $table = 'files';

    /**
     * @var array Relations
     */
    public $morphTo = [
        'attachment' => [],
    ];

    /**
     * @var string[] The attributes that are mass assignable.
     */
    protected $fillable = [
        'file_name',
        'title',
        'description',
        'field',
        'attachment_id',
        'attachment_type',
        'is_public',
        'sort_order',
        'data',
    ];

    /**
     * @var string[] The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * @var string[] Known image extensions.
     */
    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @var array<int, string> Hidden fields from array/json access
     */
    protected $hidden = ['attachment_type', 'attachment_id', 'is_public'];

    /**
     * @var array Add fields to array/json access
     */
    protected $appends = ['path', 'extension'];

    /**
     * @var mixed A local file name or an instance of an uploaded file,
     * objects of the \Symfony\Component\HttpFoundation\File\UploadedFile class.
     */
    public $data = null;

    /**
     * @var array Mime types
     */
    protected $autoMimeTypes = [
        'docx' => 'application/msword',
        'xlsx' => 'application/excel',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        'svg'  => 'image/svg+xml',
    ];

    //
    // Constructors
    //

    /**
     * Creates a file object from a file an uploaded file.
     *
     * @param UploadedFile $uploadedFile The uploaded file.
     * @return static
     */
    public function fromPost($uploadedFile)
    {
        $this->file_name = $uploadedFile->getClientOriginalName();
        $this->file_size = $uploadedFile->getSize();
        $this->content_type = $uploadedFile->getMimeType();
        $this->disk_name = $this->getDiskName();

        /*
         * getRealPath() can be empty for some environments (IIS)
         */
        $realPath = empty(trim($uploadedFile->getRealPath()))
            ? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
            : $uploadedFile->getRealPath();

        $this->putFile($realPath, $this->disk_name);

        return $this;
    }

    /**
     * Creates a file object from a file on the local filesystem.
     *
     * @param string $filePath The path to the file.
     * @return static
     */
    public function fromFile($filePath, $filename = null)
    {
        $file = new FileObj($filePath);
        $this->file_name = empty($filename) ? $file->getFilename() : $filename;
        $this->file_size = $file->getSize();
        $this->content_type = $file->getMimeType();
        $this->disk_name = $this->getDiskName();

        $this->putFile($file->getRealPath(), $this->disk_name);

        return $this;
    }

    /**
     * Creates a file object from a file on the disk returned by $this->getDisk()
     */
    public function fromStorage(string $filePath): static
    {
        $disk = $this->getDisk();

        if (!$disk->exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('File `%s` was not found on the storage disk', $filePath));
        }

        if (empty($this->file_name)) {
            $this->file_name = basename($filePath);
        }
        if (empty($this->content_type)) {
            $this->content_type = $disk->mimeType($filePath);
        }

        $this->file_size = $disk->size($filePath);
        $this->disk_name = $this->getDiskName();

        if (!$disk->copy($filePath, $this->getDiskPath())) {
            throw new ApplicationException(sprintf('Unable to copy `%s` to `%s`', $filePath, $this->getDiskPath()));
        }

        return $this;
    }

    /**
     * Creates a file object from raw data.
     *
     * @param string $data The raw data.
     * @param string $filename The name of the file.
     * @return static
     */
    public function fromData($data, $filename)
    {
        $tempName = str_replace('.', '', uniqid('', true)) . '.tmp';
        $tempPath = temp_path($tempName);
        FileHelper::put($tempPath, $data);

        $file = $this->fromFile($tempPath, basename($filename));
        FileHelper::delete($tempPath);

        return $file;
    }

    /**
     * Creates a file object from url
     *
     * @param string $url The URL to retrieve and store.
     * @param string|null $filename The name of the file. If null, the filename will be extracted from the URL.
     * @return static
     */
    public function fromUrl($url, $filename = null)
    {
        $data = Http::get($url);

        if ($data->code != 200) {
            throw new Exception(sprintf('Error getting file "%s", error code: %d', $data->url, $data->code));
        }

        if (empty($filename)) {
            // Parse the URL to get the path info
            $filePath = parse_url($data->url, PHP_URL_PATH);

            // Get the filename from the path
            $filename = pathinfo($filePath)['filename'];

            // Attempt to detect the extension from the reported Content-Type, fall back to the original path extension
            // if not able to guess
            $mimesToExt = array_flip($this->autoMimeTypes);
            $headers = array_change_key_case($data->headers, CASE_LOWER);
            if (!empty($headers['content-type']) && isset($mimesToExt[$headers['content-type']])) {
                $ext = $mimesToExt[$headers['content-type']];
            } else {
                $ext = pathinfo($filePath)['extension'] ?? '';
            }

            if (!empty($ext)) {
                $ext = '.' . $ext;
            }

            // Generate the filename
            $filename = "{$filename}{$ext}";
        }

        return $this->fromData($data, $filename);
    }

    //
    // Attribute mutators
    //

    /**
     * Helper attribute for getPath.
     *
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->getPath();
    }

    /**
     * Helper attribute for getExtension.
     *
     * @return string
     */
    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }

    /**
     * Used only when filling attributes.
     *
     * @param mixed $value
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->data = $value;
    }

    /**
     * Helper attribute for get image width.
     *
     * Returns `null` if this file is not an image.
     *
     * @return string|int|null
     */
    public function getWidthAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[0];
        }

        return null;
    }

    /**
     * Helper attribute for get image height.
     *
     * Returns `null` if this file is not an image.
     *
     * @return string|int|null
     */
    public function getHeightAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[1];
        }

        return null;
    }

    /**
     * Helper attribute for file size in human format.
     *
     * @return string
     */
    public function getSizeAttribute()
    {
        return $this->sizeToString();
    }

    //
    // Raw output
    //

    /**
     * Outputs the raw file contents.
     *
     * @param string $disposition The Content-Disposition to set, defaults to `inline`
     * @param bool $returnResponse Defaults to `false`, returns a Response object instead of directly outputting to the
     *  browser
     * @return \Illuminate\Http\Response|void
     */
    public function output($disposition = 'inline', $returnResponse = false)
    {
        $response = response($this->getContents())->withHeaders([
            'Content-type'        => $this->getContentType(),
            'Content-Disposition' => $disposition . '; filename="' . $this->file_name . '"',
            'Cache-Control'       => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Accept-Ranges'       => 'bytes',
            'Content-Length'      => $this->file_size,
        ]);

        if ($returnResponse) {
            return $response;
        }

        $response->sendHeaders();
        $response->sendContent();
    }

    /**
     * Outputs the raw thumbfile contents.
     *
     * @param int $width
     * @param int $height
     * @param array $options [
     *                  'mode'      => 'auto',
     *                  'offset'    => [0, 0],
     *                  'quality'   => 90,
     *                  'sharpen'   => 0,
     *                  'interlace' => false,
     *                  'extension' => 'auto',
     *                  'disposition' => 'inline',
     *              ]
     * @param bool $returnResponse Defaults to `false`, returns a Response object instead of directly outputting to the
     *  browser
     * @return \Illuminate\Http\Response|void
     */
    public function outputThumb($width, $height, $options = [], $returnResponse = false)
    {
        $disposition = array_get($options, 'disposition', 'inline');
        $options = $this->getDefaultThumbOptions($options);
        $this->getThumb($width, $height, $options);
        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $contents = $this->getContents($thumbFile);

        $response = response($contents)->withHeaders([
            'Content-type'        => $this->getContentType(),
            'Content-Disposition' => $disposition . '; filename="' . basename($thumbFile) . '"',
            'Cache-Control'       => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Accept-Ranges'       => 'bytes',
            'Content-Length'      => mb_strlen($contents, '8bit'),
        ]);

        if ($returnResponse) {
            return $response;
        }

        $response->sendHeaders();
        $response->sendContent();
    }

    //
    // Getters
    //

    /**
     * Returns the cache key used for the hasFile method
     *
     * @param string|null $path The path to get the cache key for
     * @return string
     */
    public function getCacheKey($path = null)
    {
        if (empty($path)) {
            $path = $this->getDiskPath();
        }

        return 'file_exists::' . $path;
    }

    /**
     * Returns the file name without path
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->file_name;
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public function getExtension()
    {
        return FileHelper::extension($this->file_name);
    }

    /**
     * Returns the last modification date as a UNIX timestamp.
     *
     * @param string|null $fileName
     * @return int
     */
    public function getLastModified($fileName = null)
    {
        return $this->storageCmd('lastModified', $this->getDiskPath($fileName));
    }

    /**
     * Returns the file content type.
     *
     * Returns `null` if the file content type cannot be determined.
     *
     * @return string|null
     */
    public function getContentType()
    {
        if ($this->content_type !== null) {
            return $this->content_type;
        }

        $ext = $this->getExtension();
        if (isset($this->autoMimeTypes[$ext])) {
            return $this->content_type = $this->autoMimeTypes[$ext];
        }

        return null;
    }

    /**
     * Get file contents from storage device.
     *
     * @param string|null $fileName
     * @return string
     */
    public function getContents($fileName = null)
    {
        return $this->storageCmd('get', $this->getDiskPath($fileName));
    }

    /**
     * Returns the public address to access the file.
     *
     * @param string|null $fileName
     * @return string
     */
    public function getPath($fileName = null)
    {
        if (empty($fileName)) {
            $fileName = $this->disk_name;
        }
        return $this->getPublicPath() . $this->getPartitionDirectory() . $fileName;
    }

    /**
     * Returns a local path to this file. If the file is stored remotely,
     * it will be downloaded to a temporary directory.
     *
     * @return string
     */
    public function getLocalPath()
    {
        if ($this->isLocalStorage()) {
            return $this->getLocalRootPath() . '/' . $this->getDiskPath();
        }

        $itemSignature = md5($this->getPath()) . $this->getLastModified();

        $cachePath = $this->getLocalTempPath($itemSignature . '.' . $this->getExtension());

        if (!FileHelper::exists($cachePath)) {
            $this->copyStorageToLocal($this->getDiskPath(), $cachePath);
        }

        return $cachePath;
    }

    /**
     * Returns the path to the file, relative to the storage disk.
     *
     * @param string|null $fileName
     * @return string
     */
    public function getDiskPath($fileName = null)
    {
        if (empty($fileName)) {
            $fileName = $this->disk_name;
        }
        return $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName;
    }

    /**
     * Determines if the file is flagged "public" or not.
     *
     * @return bool
     */
    public function isPublic()
    {
        if (array_key_exists('is_public', $this->attributes)) {
            return (bool) $this->attributes['is_public'];
        }

        if (isset($this->is_public)) {
            return (bool) $this->is_public;
        }

        return true;
    }

    /**
     * Returns the file size as string.
     *
     * @return string
     */
    public function sizeToString()
    {
        return FileHelper::sizeToString($this->file_size);
    }

    //
    // Events
    //

    /**
     * Before the model is saved
     * - check if new file data has been supplied, eg: $model->data = Input::file('something');
     *
     * @return void
     */
    public function beforeSave()
    {
        /*
         * Process the data property
         */
        if ($this->data !== null) {
            if ($this->data instanceof UploadedFile) {
                $this->fromPost($this->data);
            } elseif (file_exists($this->data)) {
                $this->fromFile($this->data);
            } else {
                $this->fromStorage($this->data);
            }

            $this->data = null;
        }
    }

    /**
     * After model is deleted
     * - clean up it's thumbnails
     *
     * @return void
     */
    public function afterDelete()
    {
        try {
            $this->deleteThumbs();
            $this->deleteFile();
        } catch (Exception $ex) {
        }
    }

    //
    // Image handling
    //

    /**
     * Checks if the file extension is an image and returns true or false.
     *
     * @return bool
     */
    public function isImage()
    {
        return in_array(strtolower($this->getExtension()), static::$imageExtensions);
    }

    /**
     * Get image dimensions
     *
     * @return array|false
     */
    protected function getImageDimensions()
    {
        return getimagesize($this->getLocalPath());
    }

    /**
     * Generates and returns a thumbnail path.
     *
     * @param integer $width
     * @param integer $height
     * @param array $options [
     *                  'mode'      => 'auto',
     *                  'offset'    => [0, 0],
     *                  'quality'   => 90,
     *                  'sharpen'   => 0,
     *                  'interlace' => false,
     *                  'extension' => 'auto',
     *              ]
     * @return string The URL to the generated thumbnail
     */
    public function getThumb($width, $height, $options = [])
    {
        if (!$this->isImage()) {
            return $this->getPath();
        }

        $width = (int) $width;
        $height = (int) $height;

        $options = $this->getDefaultThumbOptions($options);

        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $thumbPath = $this->getDiskPath($thumbFile);
        $thumbPublic = $this->getPath($thumbFile);

        if (!$this->hasFile($thumbFile)) {
            if ($this->isLocalStorage()) {
                $this->makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options);
            } else {
                $this->makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options);
            }
        }

        return $thumbPublic;
    }

    /**
     * Generates a thumbnail filename.
     *
     * @param integer $width
     * @param integer $height
     * @param array $options [
     *                  'mode'      => 'auto',
     *                  'offset'    => [0, 0],
     *                  'quality'   => 90,
     *                  'sharpen'   => 0,
     *                  'interlace' => false,
     *                  'extension' => 'auto',
     *              ]
     * @return string The filename of the thumbnail
     */
    public function getThumbFilename($width, $height, $options = [])
    {
        $options = $this->getDefaultThumbOptions($options);
        return implode('_', [
            'thumb',
            (string) $this->id,
            (string) $width,
            (string) $height,
            (string) $options['offset'][0],
            (string) $options['offset'][1],
            (string) $options['mode'] . '.' . (string) $options['extension'],
        ]);
    }

    /**
     * Returns the default thumbnail options.
     *
     * @param array $overrideOptions Overridden options
     * @return array
     */
    protected function getDefaultThumbOptions($overrideOptions = [])
    {
        $defaultOptions = [
            'mode'      => 'auto',
            'offset'    => [0, 0],
            'quality'   => 90,
            'sharpen'   => 0,
            'interlace' => false,
            'extension' => 'auto',
        ];

        if (!is_array($overrideOptions)) {
            $overrideOptions = ['mode' => $overrideOptions];
        }

        $options = array_merge($defaultOptions, $overrideOptions);

        $options['mode'] = strtolower($options['mode']);

        if (strtolower($options['extension']) == 'auto') {
            $options['extension'] = strtolower($this->getExtension());
        }

        return $options;
    }

    /**
     * Generate the thumbnail based on the local file system.
     *
     * This step is necessary to simplify things and ensure the correct file permissions are given
     * to the local files.
     *
     * @param string $thumbFile
     * @param string $thumbPath
     * @param int $width
     * @param int $height
     * @param array $options
     * @return void
     */
    protected function makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options)
    {
        $rootPath = $this->getLocalRootPath();
        $filePath = $rootPath.'/'.$this->getDiskPath();
        $thumbPath = $rootPath.'/'.$thumbPath;

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($thumbPath);
        } else {
            /*
            * Generate thumbnail
            */
            try {
                Resizer::open($filePath)
                    ->resize($width, $height, $options)
                    ->save($thumbPath)
                ;
            } catch (Exception $ex) {
                Log::error($ex);
                BrokenImage::copyTo($thumbPath);
            }
        }

        FileHelper::chmod($thumbPath);
    }

    /**
     * Generate the thumbnail based on a remote storage engine.
     *
     * @param string $thumbFile
     * @param string $thumbPath
     * @param int $width
     * @param int $height
     * @param array $options
     * @return void
     */
    protected function makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options)
    {
        $tempFile = $this->getLocalTempPath();
        $tempThumb = $this->getLocalTempPath($thumbFile);

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($tempThumb);
        } else {
            /*
            * Generate thumbnail
            */
            $this->copyStorageToLocal($this->getDiskPath(), $tempFile);

            try {
                Resizer::open($tempFile)
                    ->resize($width, $height, $options)
                    ->save($tempThumb)
                ;
            } catch (Exception $ex) {
                Log::error($ex);
                BrokenImage::copyTo($tempThumb);
            }

            FileHelper::delete($tempFile);
        }

        /*
         * Publish to storage and clean up
         */
        $this->copyLocalToStorage($tempThumb, $thumbPath);
        FileHelper::delete($tempThumb);
    }

    /**
     * Delete all thumbnails for this file.
     *
     * @return void
     */
    public function deleteThumbs()
    {
        $pattern = 'thumb_'.$this->id.'_';

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $allFiles = $this->storageCmd('files', $directory);
        $collection = [];
        foreach ($allFiles as $file) {
            if (starts_with(basename($file), $pattern)) {
                $collection[] = $file;
            }
        }

        /*
         * Delete the collection of files
         */
        if (!empty($collection)) {
            if ($this->isLocalStorage()) {
                FileHelper::delete($collection);
            } else {
                $this->getDisk()->delete($collection);
            }
        }
    }

    //
    // File handling
    //

    /**
     * Generates a disk name from the supplied file name.
     *
     * @return string
     */
    protected function getDiskName()
    {
        if ($this->disk_name !== null) {
            return $this->disk_name;
        }

        $ext = strtolower($this->getExtension());

        // If file was uploaded without extension, attempt to guess it
        if (!$ext && $this->data instanceof UploadedFile) {
            $ext = $this->data->guessExtension();
        }

        $name = str_replace('.', '', uniqid('', true));

        return $this->disk_name = !empty($ext) ? $name.'.'.$ext : $name;
    }

    /**
     * Returns a temporary local path to work from.
     *
     * @param string|null $path Optional path to append to the temp path
     * @return string
     */
    protected function getLocalTempPath($path = null)
    {
        if (!$path) {
            return $this->getTempPath() . '/' . md5($this->getDiskPath()) . '.' . $this->getExtension();
        }

        return $this->getTempPath() . '/' . $path;
    }

    /**
     * Saves a file
     *
     * @param string $sourcePath An absolute local path to a file name to read from.
     * @param string|null $destinationFileName A storage file name to save to.
     * @return bool
     */
    protected function putFile($sourcePath, $destinationFileName = null)
    {
        if (!$destinationFileName) {
            $destinationFileName = $this->disk_name;
        }

        $destinationPath = $this->getStorageDirectory() . $this->getPartitionDirectory();

        if (!$this->isLocalStorage()) {
            return $this->copyLocalToStorage($sourcePath, $destinationPath . $destinationFileName);
        }

        /*
         * Using local storage, tack on the root path and work locally
         * this will ensure the correct permissions are used.
         */
        $destinationPath = $this->getLocalRootPath() . '/' . $destinationPath;

        /*
         * Verify the directory exists, if not try to create it. If creation fails
         * because the directory was created by a concurrent process then proceed,
         * otherwise trigger the error.
         */
        if (
            !FileHelper::isDirectory($destinationPath) &&
            !FileHelper::makeDirectory($destinationPath, 0777, true, true)
        ) {
            trigger_error(error_get_last()['message'], E_USER_WARNING);
        }

        return FileHelper::copy($sourcePath, $destinationPath . $destinationFileName);
    }

    /**
     * Delete file contents from storage device.
     *
     * @param string|null $fileName
     * @return void
     */
    protected function deleteFile($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $filePath = $directory . $fileName;

        if ($this->storageCmd('exists', $filePath)) {
            $this->storageCmd('delete', $filePath);
        }

        Cache::forget($this->getCacheKey($filePath));
        $this->deleteEmptyDirectory($directory);
    }

    /**
     * Check file exists on storage device.
     *
     * @param string|null $fileName
     * @return bool
     */
    protected function hasFile($fileName = null)
    {
        $filePath = $this->getDiskPath($fileName);

        $result = Cache::rememberForever($this->getCacheKey($filePath), function () use ($filePath) {
            return $this->storageCmd('exists', $filePath);
        });

        // Forget negative results
        if (!$result) {
            Cache::forget($this->getCacheKey($filePath));
        }

        return $result;
    }

    /**
     * Checks if directory is empty then deletes it, three levels up to match the partition directory.
     *
     * @param string|null $dir Directory to check and delete if empty.
     * @return void
     */
    protected function deleteEmptyDirectory($dir = null)
    {
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);
    }

    /**
     * Returns true if a directory contains no files.
     *
     * @param string|null $dir Directory to check.
     * @return bool
     */
    protected function isDirectoryEmpty($dir = null)
    {
        return count($this->storageCmd('allFiles', $dir)) === 0;
    }

    //
    // Storage interface
    //

    /**
     * Calls a method against File or Storage depending on local storage.
     *
     * This allows local storage outside the storage/app folder and is also good for performance. For local storage,
     * *every* argument is prefixed with the local root path. Props to Laravel for the unified interface.
     *
     * @return mixed
     */
    protected function storageCmd()
    {
        $args = func_get_args();
        $command = array_shift($args);
        $result = null;

        if ($this->isLocalStorage()) {
            $interface = 'File';
            $path = $this->getLocalRootPath();
            $args = array_map(function ($value) use ($path) {
                return $path . '/' . $value;
            }, $args);

            $result = forward_static_call_array([$interface, $command], $args);
        } else {
            $result = call_user_func_array([$this->getDisk(), $command], $args);
        }

        return $result;
    }

    /**
     * Copy the Storage to local file
     *
     * @param string $storagePath
     * @param string $localPath
     * @return int The filesize of the copied file.
     */
    protected function copyStorageToLocal($storagePath, $localPath)
    {
        return FileHelper::put($localPath, $this->getDisk()->get($storagePath));
    }

    /**
     * Copy the local file to Storage
     *
     * @param string $storagePath
     * @param string $localPath
     * @return string|bool
     */
    protected function copyLocalToStorage($localPath, $storagePath)
    {
        return $this->getDisk()->put($storagePath, FileHelper::get($localPath), $this->isPublic() ? 'public' : null);
    }

    //
    // Configuration
    //

    /**
     * Returns the maximum size of an uploaded file as configured in php.ini in kilobytes (rounded)
     *
     * @return float
     */
    public static function getMaxFilesize()
    {
        return round(UploadedFile::getMaxFilesize() / 1024);
    }

    /**
     * Define the internal storage path, override this method to define.
     *
     * @return string
     */
    public function getStorageDirectory()
    {
        if ($this->isPublic()) {
            return 'uploads/public/';
        }

        return 'uploads/protected/';
    }

    /**
     * Define the public address for the storage path.
     *
     * @return string
     */
    public function getPublicPath()
    {
        if ($this->isPublic()) {
            return 'http://localhost/uploads/public/';
        }

        return 'http://localhost/uploads/protected/';
    }

    /**
     * Define the internal working path, override this method to define.
     *
     * @return string
     */
    public function getTempPath()
    {
        $path = temp_path() . '/uploads';

        if (!FileHelper::isDirectory($path)) {
            FileHelper::makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    /**
     * Returns the storage disk the file is stored on
     *
     * @return Filesystem
     */
    public function getDisk()
    {
        return Storage::disk();
    }

    /**
     * Returns true if the storage engine is local.
     *
     * @return bool
     */
    protected function isLocalStorage()
    {
        return FileHelper::isLocalDisk($this->getDisk());
    }

    /**
     * Generates a partition for the file.
     *
     * For example, returns `/ABC/DE1/234` for an name of `ABCDE1234`.
     *
     * @return string
     */
    protected function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->disk_name, 3), 0, 3)) . '/';
    }

    /**
     * If working with local storage, determine the absolute local path.
     *
     * @return string
     */
    protected function getLocalRootPath()
    {
        return storage_path() . '/app';
    }
}
