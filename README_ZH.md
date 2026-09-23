# File System for PHP

[![TESTS](https://github.com/wilkques/php-filesystem/actions/workflows/ci.yml/badge.svg)](https://github.com/wilkques/php-filesystem/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/wilkques/filesystem/v/stable)](https://packagist.org/packages/wilkques/filesystem)
[![License](https://poser.pugx.org/wilkques/filesystem/license)](https://packagist.org/packages/wilkques/filesystem)

[English](README.md) | 繁體中文

一個本地檔案系統輔助函式庫——檔案／目錄的讀取、寫入、複製、移動、刪除、掃描，並保持與 **PHP 5.3** 相容。

## 需求

- PHP >= 5.3（已在 5.3、5.6、7.0、7.1、7.2、7.3、7.4、8.0、8.1、8.2、8.3 測試過）

## 安裝

```
composer require wilkques/filesystem
```

## 使用方式

```php
use Wilkques\Filesystem\Filesystem;

$filesystem = Filesystem::make();

// 或用全域函式
$filesystem = filesystem();

// 建立檔案並寫入內容
$filesystem->put('/path/to/file', 'content');

// 取得檔案內容
$filesystem->get('/path/to/file'); // 'content'

// 附加／前置內容
$filesystem->append('/path/to/file', ' more');
$filesystem->prepend('/path/to/file', 'first ');

// 刪除檔案
$filesystem->delete('/path/to/file');
```

`Filesystem::make()`（跟 `filesystem()` 全域函式）永遠回傳**同一個共用實例**，透過 `Wilkques\Container\Container` 解析。想要獨立、不共用的實例的話，直接 `new Filesystem()` 也可以（測試套件用的就是這種方式）。

### 是否跟進符號連結（symlink）

預設情況下，目錄掃描（`directories()`、`files()`、`allFiles()`、`in()`）**不會**跟進符號連結——連結項目會被列出來，但不會往下掃描它指向的內容，這樣可以避免 symlink 循環，也避免掃到預期目錄以外的地方。

```php
// 每個實例各自設定，透過建構子
$filesystem = new Filesystem(true);

// 每個實例各自設定，用 fluent method
$filesystem = (new Filesystem())->followLinks();

// 全域設定，讓所有透過 container 解析出來的 Filesystem
//（Filesystem::make()、filesystem()、或被注入到其他類別建構子的）都套用
// ——要在任何人第一次解析 Filesystem 之前註冊好
use Wilkques\Container\Container;

Container::getInstance()
    ->when(Filesystem::class)
    ->needs('$followLinks')
    ->give(true);
```

## API 參考

以下每個方法都附上實際可執行的範例（來自測試套件 `tests/FilesystemTest.php`，已在 PHP 5.3.10、7.4、8.3 上驗證過）。

| 方法 | 說明 | 範例 |
| --- | --- | --- |
| `exists($path)` | 判斷檔案或目錄是否存在。 | `$fs->exists('/tmp/a.txt'); // true` |
| `missing($path)` | `exists()` 的相反。 | `$fs->missing('/tmp/nope.txt'); // true` |
| `isFile($file)` | 判斷給定路徑是否為檔案。 | `$fs->isFile('/tmp/a.txt'); // true` |
| `isDirectory($directory)` | 判斷給定路徑是否為目錄。 | `$fs->isDirectory('/tmp'); // true` |
| `isReadable($path)` | 判斷給定路徑是否可讀。 | `$fs->isReadable('/tmp/a.txt'); // true` |
| `isWritable($path)` | 判斷給定路徑是否可寫。 | `$fs->isWritable('/tmp/a.txt'); // true` |
| `get($path, $lock = false)` | 取得檔案內容；檔案不存在時會丟例外。`$lock = true` 會走 `sharedGet()`。 | `$fs->get('/tmp/a.txt'); // 'hello world'` |
| `sharedGet($path)` | 用共享（讀取）鎖取得檔案內容。 | `$fs->sharedGet('/tmp/a.txt'); // 'hello world'` |
| `put($path, $data, $lock = false)` | 寫入檔案內容（建立或覆寫）。 | `$fs->put('/tmp/a.txt', 'hello world');` |
| `replace($path, $content)` | 原子性地替換檔案內容（先寫暫存檔再改名）。 | `$fs->replace('/tmp/a.txt', 'new content');` |
| `replaceInFile($search, $replace, $path)` | 在檔案內取代指定字串。 | `$fs->replaceInFile('world', 'there', '/tmp/a.txt');` |
| `prepend($path, $data)` | 把內容加到檔案最前面（檔案不存在就建立）。 | `$fs->prepend('/tmp/a.txt', 'first-'); // 'first-...'` |
| `append($path, $data)` | 把內容加到檔案最後面。 | `$fs->append('/tmp/a.txt', '-last'); // '...-last'` |
| `delete($paths)` | 刪除一個檔案，或一組檔案。 | `$fs->delete('/tmp/a.txt'); // true` |
| `move($path, $target)` | 移動（重新命名）檔案。 | `$fs->move('/tmp/a.txt', '/tmp/b.txt');` |
| `copy($path, $target)` | 複製檔案。 | `$fs->copy('/tmp/a.txt', '/tmp/b.txt');` |
| `chmod($path, $mode = null)` | 取得（不傳 `$mode`）或設定路徑的 UNIX 權限。 | `$fs->chmod('/tmp/a.txt'); // '0644'` |
| `size($path)` | 取得檔案大小（bytes）。 | `$fs->size('/tmp/a.txt'); // 5` |
| `lastModified($path)` | 取得檔案最後修改時間（UNIX timestamp）。 | `$fs->lastModified('/tmp/a.txt'); // 例如 1700000000` |
| `hash($path, $algorithm = 'md5')` | 取得檔案的雜湊值。 | `$fs->hash('/tmp/a.txt'); // md5_file() 的值` |
| `type($path)` | 取得路徑的類型。 | `$fs->type('/tmp/a.txt'); // 'file'`，`$fs->type('/tmp'); // 'dir'` |
| `mimeType($path)` | 取得檔案的 MIME type。 | `$fs->mimeType('/tmp/a.txt'); // 'text/plain'` |
| `name($path)` | 不含副檔名的檔名。 | `$fs->name('/tmp/a.txt'); // 'a'` |
| `basename($path)` | 含副檔名的檔名。 | `$fs->basename('/tmp/a.txt'); // 'a.txt'` |
| `dirname($path)` | 上層目錄路徑。 | `$fs->dirname('/tmp/a.txt'); // '/tmp'` |
| `extension($path)` | 副檔名。 | `$fs->extension('/tmp/a.txt'); // 'txt'` |
| `getRequire($path, $data = [])` | `require` 一個 PHP 檔並回傳其回傳值；`$data` 會用 `extract()` 帶入該檔案的作用域。 | `$fs->getRequire('/tmp/config.php', ['x' => 5]); // config.php 回傳的值` |
| `requireOnce($path, $data = [])` | `require_once` 一個 PHP 檔（用於執行副作用）；回傳 `$this`。 | `$fs->requireOnce('/tmp/bootstrap.php');` |
| `ensureDirectoryExists($path, $mode = 0755, $recursive = true)` | 目錄不存在時才建立。 | `$fs->ensureDirectoryExists('/tmp/nested/dir');` |
| `makeDirectory($path, $mode = 0755, $recursive = false, $force = false)` | 建立目錄。 | `$fs->makeDirectory('/tmp/dir'); // true` |
| `moveDirectory($from, $to, $overwrite = false)` | 移動目錄。 | `$fs->moveDirectory('/tmp/src', '/tmp/dst'); // true` |
| `copyDirectory($directory, $destination, $options = null)` | 遞迴複製目錄。 | `$fs->copyDirectory('/tmp/src', '/tmp/dst'); // true` |
| `deleteDirectory($directory, $preserve = false)` | 遞迴刪除目錄；`$preserve = true` 會清空目錄但保留目錄本身。 | `$fs->deleteDirectory('/tmp/dir'); // true` |
| `deleteDirectories($directory)` | 刪除 `$directory` 底下所有第一層子目錄。 | `$fs->deleteDirectories('/tmp'); // true` |
| `cleanDirectory($directory)` | 清空目錄裡所有檔案與子目錄，保留目錄本身。 | `$fs->cleanDirectory('/tmp/dir'); // true` |
| `directories($directory)` | 列出 `$directory` 底下第一層子目錄（不含檔案）。 | `$fs->directories('/tmp'); // ['/tmp/sub1', '/tmp/sub2']` |
| `files($directory, $hidden = false)` | 列出 `$directory` 底下的檔案（非遞迴）；預設不含隱藏檔，`$hidden = true` 才會包含。 | `$fs->files('/tmp'); // ['/tmp/a.txt']` |
| `allFiles($directory, $hidden = false)` | 遞迴列出 `$directory` 底下所有檔案。 | `$fs->allFiles('/tmp'); // ['/tmp/a.txt', '/tmp/sub/b.txt', ...]` |
| `glob($pattern, $flags = 0)` | `glob()` 包裝。 | `$fs->glob('/tmp/*.txt'); // ['/tmp/a.txt', '/tmp/b.txt']` |
| `followLinks()` | 開啟這個實例目錄掃描時跟進符號連結（見上方說明）。 | `$fs->followLinks();` |
| `make()` *（靜態方法）* | 透過 container 解析出共用的 `Filesystem` 實例。 | `Filesystem::make();` |

### 較底層的目錄掃描機制

`directories()`/`files()`/`allFiles()` 是平常會用到的方法。這三個方法**只**建構在 `searchInDirectory()` 之上——刻意**不**建立在 `in()`（見下方）之上，這樣共用／單例的 `Filesystem` 實例（例如 `make()`/`filesystem()` 回傳的那個）就不會在不相關的呼叫者之間洩漏狀態：

| 方法 | 說明 |
| --- | --- |
| `normalizeDir($dir)` | 去除目錄路徑結尾的斜線（`(s)ftp://` 這種 URL 除外）。 |
| `searchInDirectory($dir)` | 取得單一目錄的 `RecursiveDirectoryIterator`，會遵循 `followLinks()` 的設定。無狀態——每次呼叫都是全新的 iterator，不會存任何東西在 `$this` 上。 |

### `in()` —— 另一套獨立的、模仿 Symfony Finder 的 API

`in($dirs)` 是使用 `Filesystem` 實例的另一種、選用的方式：不是呼叫一個回傳陣列的方法，而是先「登記」一個或多個目錄／glob pattern，再對實例本身做迭代。

| 方法 | 說明 |
| --- | --- |
| `in($dirs)` | 解析一個或多個目錄／glob pattern，準備好供迭代（fluent，回傳 `$this`）。**會跨呼叫累加**——它是把結果 merge 進實例已登記的目錄清單，不是取代，所以呼叫 `in()` 兩次以上（或在共用實例上重複使用）會把每次呼叫登記的目錄全部合併進同一次掃描。 |
| `count()` / `getIterator()` | `Filesystem` 實作了 `Countable`/`IteratorAggregate`，涵蓋 `in()` 準備好的內容，所以呼叫過 `in()` 之後可以直接 `count($fs)`、`foreach ($fs as $entry)`。**不會遞迴**——每個登記的目錄只會掃一層（跟 `files()` 一樣，不像 `allFiles()`）。 |

因為 `in()` 的狀態是累加在實例上的，如果在 `make()`/`filesystem()` 回傳的共用實例上呼叫，很容易把目錄洩漏給不相關的呼叫者。建議用這套 API 時改用 `new Filesystem()`，或是確保在你呼叫 `in()` 到讀取結果之間，沒有其他人動到同一個實例。

## 測試

```
composer install
vendor/bin/phpunit
```

## 授權

MIT
