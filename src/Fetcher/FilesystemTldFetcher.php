<?php namespace Hampel\Tlds\Fetcher;

use Hampel\Tlds\Exceptions\FilesystemException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;

class FilesystemTldFetcher implements TldFetcher
{
    /**
     * @return string TLD data
     *
     * @throws FilesystemException when fetch failed
     */
	public function fetchTlds()
	{
        $disk = Config::get('tlds.disk');
        $path = Config::get('tlds.path');

        if (!Storage::disk($disk)->exists($path))
        {
            throw new FilesystemException("No file found at {$path}");
        }

		try
		{
			$data = Storage::disk($disk)->get($path);
		}
		catch (UnableToReadFile $e)
		{
            throw new FilesystemException($e->getMessage(), $e->getCode(), $e);
		}

		return $data;
	}
}
