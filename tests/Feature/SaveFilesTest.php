<?php

namespace Fboseca\Filesmanager\Tests;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SaveFilesTest extends TestCase {


	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * @group save
	 */
	public function testSaveFile() {
		$file = UploadedFile::fake()->image('avatar.jpg');

		$fileSaved = $this->user1->addFile($file);
		Storage::disk($fileSaved->disk)->assertExists($fileSaved->url);
		$this->assertSame('files', $fileSaved->folder);
		$this->assertSame('img', config($this->configFile . '.extensions.jpg'));
		$this->assertSame('jpg', $fileSaved->file_extension);
		$this->assertSame('public', $fileSaved->disk);
		$this->assertSame('local', $fileSaved->driver);
		$this->assertSame('', $fileSaved->description);
		$this->assertSame('', $fileSaved->group);
	}

	/**
	 * @group save
	 */
	public function testSaveFileWithParameters() {
		$file = UploadedFile::fake()->image('avatar.doc');
		$fileSaved = $this->user1->addFile($file, [
			"group"       => "gallery",
			"name"        => "my Name File",
			"description" => "A description for a file"
		]);

		Storage::disk($fileSaved->disk)->assertExists($fileSaved->url);
		$this->assertSame('files', $fileSaved->folder);
		$this->assertSame('my_name_file.doc', $fileSaved->name);
		$this->assertSame('word', config($this->configFile . '.extensions.doc'));
		$this->assertSame('doc', $fileSaved->file_extension);
		$this->assertSame('public', $fileSaved->disk);
		$this->assertSame('local', $fileSaved->driver);
		$this->assertSame('A description for a file', $fileSaved->description);
		$this->assertSame('gallery', $fileSaved->group);
	}

	/**
	 * @group save
	 */
	public function testSaveFileWithoutExtension() {
		$file = UploadedFile::fake()->create('name');
		$fileSaved = $this->user1->addFile($file, [
			"group"       => "gallery",
			"name"        => "my Name File",
			"description" => "A description for a file"
		]);

		//the extension of file must be txt
		Storage::disk($fileSaved->disk)->assertExists($fileSaved->url);
		$this->assertSame('files', $fileSaved->folder);
		$this->assertSame('my_name_file.txt', $fileSaved->name);
		$this->assertSame('file', config($this->configFile . '.extensions.*'));
		$this->assertSame('txt', $fileSaved->file_extension);
		$this->assertSame('public', $fileSaved->disk);
		$this->assertSame('local', $fileSaved->driver);
		$this->assertSame('A description for a file', $fileSaved->description);
		$this->assertSame('gallery', $fileSaved->group);
	}

	/**
	 * @group save
	 */
	public function testSaveFilesWithSameName() {
		$file = UploadedFile::fake()->create('name.pdf');
		$file1 = $this->user1->addFile($file, [
			"name" => "my Name File",
		]);
		$file2 = $this->user1->addFile($file, [
			"group" => "copies",
			"name"  => "my Name File"
		]);

		$this->assertSame('my_name_file.pdf', $file1->name);
		$this->assertSame('', $file1->group);
		$this->assertSame('my_name_file_(1).pdf', $file2->name);
		$this->assertSame('copies', $file2->group);
	}

	/**
	 * @group save
	 */
	public function testSaveAvatar() {
		//save logo
		$file = UploadedFile::fake()->image('avatar.png', 100);

		$fileSaved = $this->user1->setAvatar($file);
		Storage::disk($fileSaved->disk)->assertExists($fileSaved->url);
		$this->assertSame('avatar', $fileSaved->group);
		$this->assertSame(1, $this->user1->files()->count());
		$response = $this->get($this->user1->avatar->src);
		$response->assertStatus(200);

		$fileSaved = $this->user1->setAvatar($file, [
			"name"   => "logo2",
			"disk"   => "private",
			"folder" => "avatars",
		]);
		Storage::disk($fileSaved->disk)->assertExists($fileSaved->url);
		$this->assertSame('avatar', $fileSaved->group);
		$this->assertSame('logo2.png', $fileSaved->name);
		$this->assertSame('private', $fileSaved->disk);
		$this->assertSame('avatars', $fileSaved->folder);
		$this->assertSame(1, $this->user1->files()->count());

	}

	/**
	 * @group save
	 */
	public function testSaveOnNewFolder() {
		$file2 = UploadedFile::fake()->create('document.pdf');
		//change folder
		$fileSaved = $this->user1->folder('testing/files')->addFile($file2);

		Storage::disk($fileSaved->disk)->assertExists('testing/files/' . $fileSaved->name);
		$this->assertSame('testing/files', $fileSaved->folder);
	}

	/**
	 * @group save
	 */
	public function testSaveOnNewDisk() {
		$file2 = UploadedFile::fake()->create('document.pdf');
		//change folder
		$fileSaved = $this->user1->disk('private')->addFile($file2);

		Storage::disk('private')->assertExists($fileSaved->url);
	}

	/**
	 * @group save
	 */
	public function testSaveOnNewDiskAndFolder() {
		$file2 = UploadedFile::fake()->create('document.pdf');
		//change folder
		$fileSaved = $this->user1->disk('private')->folder('testing/files')->addFile($file2);

		Storage::disk('private')->assertExists('testing/files/' . $fileSaved->name);
		$this->assertSame('testing/files', $fileSaved->folder);

		$fileSaved2 = $this->user1->addFile($file2);
		Storage::disk('private')->assertExists('testing/files/' . $fileSaved2->name);
		$this->assertSame('testing/files', $fileSaved2->folder);

		$fileSaved3 = $this->user1->addFile($file2, [
			"folder" => "new",
			"disk"   => "public"
		]);
		Storage::disk('public')->assertExists('new/' . $fileSaved3->name);
		$this->assertSame('new', $fileSaved3->folder);
		$this->assertSame('public', $fileSaved3->disk);
	}

	/**
	 * @group save
	 */
	public function testSavePrivateFiles() {
		$file = UploadedFile::fake()->image('avatar.png', 100);
		$file2 = UploadedFile::fake()->create('document.pdf');
		$this->user1->disk('private')->addFile($file);
		$this->user1->disk('private')->addFile($file2);
		foreach ($this->user1->files as $file) {
			$response = $this->get($file->forceSrc);
			$response->assertStatus(200);
			$this->assertStringContainsString('private/file', $file->forceSrc);
		}
	}

	/**
	 * @group save
	 */
	public function testSaveFileWithContent() {
		$file = UploadedFile::fake()->image('avatar.png', 100);
		$file2 = $this->user1->addFileWithContent($file->getContent(), 'png', [
			"group" => "testing",
			"name"  => "test"
		]);

		Storage::disk($file2->disk)->assertExists($file2->url);
		$this->assertSame('test.png', $file2->name);
		$this->assertSame('testing', $file2->group);

		$file3 = $this->user1->addFileWithContent('hello world', 'txt', [
			"group" => "testing",
			"name"  => "test"
		]);

		Storage::disk($file3->disk)->assertExists($file3->url);
		$this->assertSame('test.txt', $file3->name);
		$this->assertSame('testing', $file3->group);
		$this->assertStringContainsString($file3->getContent, 'hello world');
	}
}