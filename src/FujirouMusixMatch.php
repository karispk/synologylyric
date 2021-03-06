<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouMusixMatch
{
    private $sitePrefix = 'https://www.musixmatch.com';

    public function __construct()
    {
    }

    public function getLyricsList($artist, $title, $info)
    {
        return $this->search($info, $artist, $title);
    }
    public function getLyrics($id, $info)
    {
        return $this->get($info, $id);
    }

    public function search($handle, $artist, $title)
    {
        $count = 0;

        $searchUrl = sprintf(
            "%s/search/%s",
            $this->sitePrefix, rawurlencode(sprintf('%s %s', $title, $artist))
        );

        $content = FujirouCommon::getContent($searchUrl);
        if (!$content) {
            return $count;
        }

        $list = $this->parseSearchResult($content);

        for ($idx = 0; $idx < count($list); $idx++) {
            $obj = $list[$idx];

            $handle->addTrackInfoToList(
                $obj['artist'],
                $obj['title'],
                $obj['id'],
                $obj['partial']
            );
        }

        return $count;
    }

    public function get($handle, $id)
    {
        $lyric = '';

        $url = sprintf("%s%s", $this->sitePrefix, $id);

        $content = FujirouCommon::getContent($url);
        if (!$content) {
            return false;
        }

        $prefix = 'var __mxmState = ';
        $suffix = ';</script>';

        $json_string = FujirouCommon::getSubString($content, $prefix, $suffix);
        if (!$json_string || $json_string === $content) {
            return false;
        }

        $json_string = str_replace($prefix, '', $json_string);
        $json_string = str_replace($suffix, '', $json_string);

        $json = json_decode($json_string, true);
        if (!$json) {
            return false;
        }

        if (!isset($json['page']['lyrics']['lyrics']['body'])) {
            return false;
        }

        $body = $json['page']['lyrics']['lyrics']['body'];
        $title = $json['page']['track']['name'];
        $artist = $json['page']['track']['artistName'];

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from musixmatch',
            "$artist - $title",
            $body
        );

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        $prefix = '<a class="title" ';
        $suffix = '</div></li>';
        $block = FujirouCommon::getSubString($content, $prefix, $suffix);

        $pattern = '/<a class="title" .*?><span.*?>(.*?)<\/span><\/a>/';
        $value = FujirouCommon::getFirstMatch($block, $pattern);
        if (!$value) {
            return $result;
        }
        $title = FujirouCommon::decodeHTML($value);

        $pattern = '/<a class="artist".*?>(.*?)<\/a>/';
        $value = FujirouCommon::getFirstMatch($block, $pattern);
        if (!$value) {
            return $result;
        }
        $artist = FujirouCommon::decodeHTML($value);

        $pattern = '/<a class="title" href="(.+?)".*?><span.*?>.*?<\/span><\/a>/';
        $value = FujirouCommon::getFirstMatch($block, $pattern);
        if (!$value) {
            return $result;
        }
        $id = $value;

        $item = array(
            'artist' => $artist,
            'title'  => $title,
            'id'     => $id,
            'partial'=> ''
        );

        array_push($result, $item);

        return $result;
    }
}
