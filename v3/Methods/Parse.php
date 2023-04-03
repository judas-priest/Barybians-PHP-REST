<?php
function Parse(string $text, string $parseMode = 'text')
{
    $text = strip_tags($text, ['<br>', '<b>', '<u>', '<i>', '<s>', '<strike>', '<a>', '<img>', '<sticker>']);
    $result = [];
    switch ($parseMode) {
        case 'html':
            $expBR = '~\<br\>+~';
            $expIMG = '~\<img.+src\="(.*?)"\/?>~';
            $expURL = '~<a\s+(?:[^>]*?\s+)?href="(.*?)".*<\/a>~';
            $expSTK = '~\<sticker.*\="(\w*)">(\d*)</sticker>~';
            $text = preg_replace($expBR, "\n", $text);
            $text = preg_replace($expIMG, '<img>$1</img>', $text);
            $text = preg_replace($expURL, '<a>$1</a>', $text);
            $text = preg_replace($expSTK, '<sticker>$1 $2</sticker>', $text);
            $exp = '~\<([a-zA-Z]*)\>(.*?)\</\1\>~';
            $exp2 = '~\<\/?(\w+)[^\]*]~';
            $exp3 = '~\<[a-zA-Z]*\>~';
            $arr = tags($text, $exp, $exp2, $exp3);
            $text = $arr['text'] ?? $text;
            $result = $arr['result'] ?? null;
            break;
        case 'bb':
            $text = htmlspecialchars($text);
            $exp = '~\[([a-zA-Z]*)\](.*?)\[/\1\]~'; //'~\[([a-zA-Z]*).?(\d)?\](.*?)\[/\1\]~';
            $exp2 = '~\[\/?(\w+)[^\]]*]~';
            $exp3 = '~\[[a-zA-Z]*\]~';
            $arr = tags($text, $exp, $exp2, $exp3);
            $text = $arr['text'] ?? $text;
            $result = $arr['result'] ?? null;
            break;
        case 'md':
            $text = htmlspecialchars($text);
            $exp = '~\[([a-zA-Z]*)\](.*?)\[/\1\]~';
            $exp2 = '~\[\/?(\w+)[^\]]*]~';
            $exp3 = '~\[[a-zA-Z]*\]~';
            $expB = '~\*\*(.*)\*\*~';
            $expI = '~\*(.+?)\*~';
            $expU = '~_(.+?)_~';
            $expS = '!\~\~(.*)\~\~!';
            $expSTK = '~\$\[(\w*)\]\((\d*)\)~';
            $expIMG = '~\!\[(.*?)\]\((.*?)\)~';
            $expURL = '~\[(.*?)\]\((.*?)\)~'; //'~(?:__|([*#]))|\[(.*?)\]\((.*?)\)~'
            $text = preg_replace($expB, '[b]$1[/b]', $text);
            $text = preg_replace($expI, '[i]$1[/i]', $text);
            $text = preg_replace($expU, '[u]$1[/u]', $text);
            $text = preg_replace($expS, '[s]$1[/s]', $text);
            $text = preg_replace($expSTK, '[sticker]$1 $2[/sticker]', $text);
            $text = preg_replace($expIMG, '[img]$2[/img]', $text);
            $text = preg_replace($expURL, '[a]$2[/a]', $text);
            $arr = tags($text, $exp, $exp2, $exp3);
            $text = $arr['text'] ?? $text;
            $result = $arr['result'] ?? null;
            break;
        default:
            $text = htmlspecialchars($text);
            //$exp = '~\https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,}~';
            break;
    }
    $result = json_encode($result, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return [$text, $result];
}


function tagsParser($item, $offset, $length, $entry)
{
    switch ($item) { //$item['1']['0']
        case 'b':
            $style = 'bold';
            $type = 'styled';
            break;
        case 'u':
            $style = 'underline';
            $type = 'styled';
            break;
        case 'i':
            $style = 'italic';
            $type = 'styled';
            break;
        case 's':
        case 'strike':
            $style = 'strike';
            $type = 'styled';
            break;
        case 'img':
            $type = 'image';
            break;
        case 'url':
        case 'a':
            $type = 'link';
            break;
        case 'sticker':
            $type = 'sticker';
            break;
    }
    $arr = ['offset' => $offset, 'length' => $length, 'type' => $type];


    switch ($type) {
        case 'styled':
            $arr['style'] = $style;
            $result = $arr;
            break;
        case 'image':
            $arr['url'] = $entry;
            $result = $arr;
            break;
        case 'link':
            $arr = GetLinkPreview($entry); //GetLinkPreview($item['2']['0']);
            $arr['offset'] = $offset;
            $arr['length'] = $length;
            $result = $arr;
            //$result[] = ['type' => 'link', 'url' => $item['0']['0'], 'title' => $title, 'favicon' => $favicon, 'timestamp' => '0', 'offset' => $offset, 'length' => mb_strlen($item['0']['0'])];
            break;
        case 'sticker':
            preg_match_all('~(\w*)\s(\d*)~', $entry, $matches);
            $arr['pack'] = $matches[1][0];
            $arr['sticker'] = $matches[2][0];
            $arr['url'] = getenv('HOST_BRB_CONTENT') . "/stickers/{$arr['pack']}/{$arr['sticker']}.png";
            $result = $arr;
            break;
    }
    return $result;
}
function GetLinkPreview(string $url, int $full = 0)
{
    $fileRegex = '~.*\/(\w*\.(mp(3|4)|wav|exe|zip|rar|apk|bin|tar(|\.gz)))(\?.*)?$~';
    $youtubeRegex = '~http(?:s?):\/\/(?:www\.)?youtu(?:be\.com\/watch\?v=|\.be\/)([\w\-\_]*)(&(amp;)?‌​[\w\?‌​=]*)?~';
    $newUrl = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $url);
    preg_match('/(?!(w+)\.)\S*(?:\w+\.)+\w+/', $newUrl, $hostname);
    // if (preg_match($youtubeRegex, $newUrl)) {
    //     echo 'dima';
    // }
    $curl = curl_init($newUrl);
    $options = [
        CURLOPT_USERAGENT => 'DuckDuckBot/1.0; (+http://duckduckgo.com/duckduckbot.html)',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_NOPROGRESS => false,
        CURLOPT_PROGRESSFUNCTION => function (
            $DownloadSize,
            $Downloaded,
            $UploadSize,
            $Uploaded
        ) {
            return ($Downloaded > (1048576)) ? true : false;
        }
    ];
    curl_setopt_array($curl, $options);
    $fp = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    $protocol = strtolower($info['scheme']) . '://';
    preg_match('~^.*\/(\w*)~', $info['content_type'], $mime);
    if ((curl_errno($curl) === 42 || curl_errno($curl) === 28 && $info['http_code'] === 200) && preg_match($fileRegex, $newUrl, $file)) {
        $json = [];
        $json['type'] = 'file-link';
        $json['url'] = $protocol . $newUrl;
        $json['title'] = $file['1'];
        $json['extension'] = $file['2'];
        $json['fileSize'] = $info['download_content_length'];
        $json['timestamp'] = time();
        return $json;
    }
    if ($fp) {
        switch ($mime['1']) {
            case 'gif':
            case 'png':
            case 'svg+xml':
            case 'tiff':
            case 'webp':
            case 'heif':
            case 'heic':
            case 'jpeg':
                $json = [];
                $json['type'] = 'image';
                $json['url'] = $protocol . $newUrl;
                return $json;
                break;
            case 'html':
                $fp = mb_convert_encoding($fp, 'HTML-ENTITIES', 'UTF-8');
                $doc = new DomDocument();
                @$doc->loadHTML($fp);
                $xpath = new DOMXPath($doc);
                $query = '//*/meta[starts-with(@property, \'og:\')] | //*/meta[starts-with(@name, \'twitter:\')]';
                $metas = $xpath->query($query);
                $rmetas = [];
                foreach ($metas as $meta) {
                    $property          = $meta->getAttribute('property');
                    $name          = $meta->getAttribute('name');
                    $content           = $meta->getAttribute('content');
                    $property          = preg_replace('~og:~s', '', $property);
                    $name          = preg_replace('~twitter:~s', '', $name);
                    $rmetas[$property] = $content;
                    $rmetas[$name] = $content;
                }
                $title = $xpath->query('//title')->item(0)->textContent ?? $url;
                $description = $xpath->query('//meta[@name="description"]/@content')->item(0)->value ?? null;
                $favicon = $xpath->query('//head//link[@rel="icon"]/attribute::href | //head//link[@rel="shortcut icon"]/attribute::href')->item(0)->value ?? null;
                $json = [];
                $json['type'] = 'link';
                $json['url'] = '';
                $json['title'] = '';
                $json['description'] = '';
                isset($title) ? $json['html-title'] = trim(mbCutString($title, 70)) : '';
                isset($description) ? $json['html-description'] = $description : '';
                isset($rmetas['title']) ? $json['meta-title'] = $rmetas['title'] : '';
                isset($rmetas['description']) ? $json['meta-description'] =  $rmetas['description'] : '';

                if (isset($rmetas['image'])) $json['meta-image'] = $rmetas['image']; // && ExistsURL($rmetas['image'])

                isset($favicon) ? $json['favicon'] = $favicon : '';
                $json['timestamp'] = time();
                foreach ($json as &$object) {
                    $object = preg_replace('/www\./', '', $object);
                    if (preg_match('/^(?!(\.\.))(\W*)?\w*?\.\w*?\.\w*\/.*/', $object, $match)) {
                        $object = preg_replace('/^\/\/?/', '', $object);
                        $object = $protocol . $object;
                    } else {
                        $object = preg_replace('/^\/|^..\//', $protocol . $hostname['0'] . '/', $object);
                    }
                    if (preg_match('/^(?!(\w*\.))\w*\/.*$/', $object, $match)) $object = $protocol . $hostname['0'] . '/' . $object;
                }
                $json['title'] = $json['meta-title'] ?? $json['html-title'];
                $json['description'] = $json['meta-description'] ?? $json['html-description'] ?? null;
                $json['url'] = $protocol . $newUrl;
                if ($full === 1) {
                    return $json;
                } else {
                    $result['type'] = $json['type'];
                    $result['url'] = $json['url'];
                    $result['title'] = $json['title'] ?? null;
                    $result['description'] = $json['description'] ?? null;
                    $result['image'] = $json['meta-image'] ?? null;
                    $result['favicon'] = $json['favicon'] ?? null;
                    $result['timestamp'] = $json['timestamp'];
                    return $result;
                }
                break;
        }
    } /*else if (!$fp) {
        $getContent = "https://api.linkpreview.net?key=b658e12c3c4a3e5dd323c3161214f9b1&fields=url,title,description,image,icon&q=$newUrl";
        $settings = stream_context_create(['http' => ['timeout' => 20,]]);
        if (false !== ($result = @file_get_contents($getContent, false, $settings))) {
            $result = json_decode($result, true);
            $result['type'] = 'link';
            $result['timestamp'] = time();
            return $result;
        }
    } */
    return ['type' => 'link', 'timestamp' => time(), 'message' => $url . ' is not available!', 'error' =>  $info['http_code']];
}

function tags(string $text, string $exp, string $exp2, string $exp3)
{
    $attachments = [];
    $result = [];
    $string = $text;
    $text = tagsRegexMaxon($text, $attachments, $exp, $exp2, $exp3);

    while (preg_match_all($exp2, $string, $dd)) {
        $string = preg_replace($exp2, '$2', $string);
    }

    foreach ($attachments as $item) {
        $result[] = tagsParser($item['tag'], $item['offset'], $item['length'], $item['entry']);
    }
    return ['text' => $string ?? null, 'result' => $result ?? null];
}

function removeTags(string $string, string $exp2)
{
    return preg_replace($exp2, '$2', $string);
}
function tagsRegexMaxon(string $substring, array &$attachments, string $exp, string $exp2, string $exp3, int $offset = 0)
{
    mb_preg_match_all(
        $exp,
        $substring,
        $matches,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
    );

    $offset_local = $offset;
    foreach ($matches as $match) {
        $entry = removeTags($match[2][0], $exp2);
        $attachments[] =
            [
                'entry' => $entry,
                'tag' => $match[1][0],
                'offset' => $match[0][1] + $offset_local,
                'length' => mb_strlen($match[2][0])
            ];
        // If string has inner tags
        if (preg_match($exp3, $match[2][0])) {
            tagsRegexMaxon($match[2][0], $attachments, $exp, $exp2, $exp3, $match[0][1] + $offset_local);
        } else {
            $offset_local -= (mb_strlen($match[0][0]) - mb_strlen($entry));
            continue;
        }
        $offset_local -= (mb_strlen($match[0][0]) - mb_strlen($entry));
    }
}

function mbCutString(string $str, $length, $postfix = '...', $encoding = 'UTF-8')
{
    if (mb_strlen($str, $encoding) <= $length) {
        return $str;
    }
    $tmp = mb_substr($str, 0, $length, $encoding);
    return mb_substr($tmp, 0, mb_strripos($tmp, ' ', 0, $encoding), $encoding) . $postfix;
}

function mb_preg_match_all($ps_pattern, $ps_subject, &$pa_matches, $pn_flags = PREG_PATTERN_ORDER, $pn_offset = 0, $ps_encoding = NULL)
{
    // WARNING! - All this function does is to correct offsets, nothing else:
    //
    if (is_null($ps_encoding))
        $ps_encoding = mb_internal_encoding();

    $pn_offset = strlen(mb_substr($ps_subject, 0, $pn_offset, $ps_encoding));
    $ret = preg_match_all($ps_pattern, $ps_subject, $pa_matches, $pn_flags, $pn_offset);

    if ($ret && ($pn_flags & PREG_OFFSET_CAPTURE))
        foreach ($pa_matches as &$ha_match)
            foreach ($ha_match as &$ha_match)
                $ha_match[1] = mb_strlen(substr($ps_subject, 0, $ha_match[1]), $ps_encoding);
    //
    // (code is independent of PREG_PATTER_ORDER / PREG_SET_ORDER)

    return $ret;
}
