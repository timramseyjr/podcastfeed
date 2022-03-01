<?php namespace Torann\PodcastFeed;

use DateTime;
use DOMDocument;

class Media
{
    /**
     * Title of media.
     *
     * @var string
     */
    private $title;

    /**
     * Itunes Title of media.
     *
     * @var string
     */
    private $itunes_title;

    /**
     * Subtitle of media.
     *
     * @var string|null
     */
    private $subtitle;

    /**
     * URL to the media web site.
     *
     * @var string
     */
    private $link;

    /**
     * Date of publication of the media.
     *
     * @var DateTime
     */
    private $pubDate;

    /**
     * description media.
     *
     * @var string
     */
    private $description;

    /**
     * URL of the media
     *
     * @var string
     */
    private $url;

    /**
     * Type of media (audio / mpeg, for example).
     *
     * @var string
     */
    private $type;

    /**
     * Author of the media.
     *
     * @var string
     */
    private $author;

    /**
     * Categories of the media.
     *
     * @var array
     */
    private $categories = [];

    /**
     * GUID of the media.
     *
     * @var string
     */
    private $guid;

    /**
     * Duration of the media only as HH:MM:SS, H:MM:SS, MM:SS or M:SS.
     *
     * @var string
     */
    private $duration;

    /**
     * Explicit flag of the media.
     *
     * @var string
     */
    private $explicit;

    /**
     * URL to the image representing the media..
     *
     * @var string
     */
    private $image;

    /**
     * Length in bytes of the media file.
     *
     * @var string
     */
    private $length;

    /**
     * Podcast Season.
     *
     * @var integer
     */
    private $season;

    /**
     * Podcast Episode.
     *
     * @var integer
     */
    private $episode;

    /**
     * Type of the episode. Full, trailer, bonus.
     *
     * @var string
     */
    private $episode_type;


    /**
     * Class constructor
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->title = $this->getValue($data, 'title');
        $this->itunes_title = $this->getValue($data, 'itunes_title');
        $this->subtitle = $this->getValue($data, 'subtitle');
        $this->description = $this->getValue($data, 'description');
        $this->pubDate = $this->getValue($data, 'publish_at');
        $this->url = $this->getValue($data, 'url');
        $this->guid = $this->getValue($data, 'guid');
        $this->type = $this->getValue($data, 'type');
        $this->duration = $this->getValue($data, 'duration');
        $this->explicit = $this->getValue($data, 'explicit');
        $this->author = $this->getValue($data, 'author');
        $this->season = $this->getValue($data, 'season');
        $this->episode = $this->getValue($data, 'episode');
        $this->image = $this->getValue($data, 'image');
        $this->length = $this->getValue($data, 'length');
        $this->link = $this->getValue($data, 'link');
        $this->episode_type = $this->getValue($data, 'episode_type','full');

        // Ensure publish date is a DateTime instance
        if (is_string($this->pubDate)) {
            $this->pubDate = new DateTime($this->pubDate);
        }
    }

    /**
     * Get value from data and escape it.
     *
     * @param  mixed  $data
     * @param  string $key
     * @param  mixed $default
     *
     * @return string
     */
    public function getValue($data, $key, $default = null)
    {
        $value = array_get($data, $key, $default);

        return htmlspecialchars($value);
    }

    /**
     * Get media publication date.
     *
     * @return  DateTime
     */
    public function getPubDate()
    {
        return $this->pubDate;
    }

    /**
     * Adds media in the DOM document setting.
     *
     * @param DOMDocument $dom
     */
    public function addToDom(DOMDocument $dom)
    {
        // Recovery of  <channel>
        $channels = $dom->getElementsByTagName("channel");
        $channel = $channels->item(0);

        // Create the <item>
        $item = $dom->createElement("item");
        $channel->appendChild($item);

        // Create the <title>
        $title = $dom->createElement("title", $this->title);
        $item->appendChild($title);

        // Create the <itunes:subtitle>
        if ($this->subtitle) {
            $itune_subtitle = $dom->createElement("itunes:subtitle", $this->subtitle);
            $item->appendChild($itune_subtitle);
        }

        if($this->itunes_title){
            $itunes_title = $dom->createElement("itunes:title", $this->itunes_title);
            $item->appendChild($itunes_title);
        }

        // Create the <description>
        $description = $dom->createElement("description");
        $description->appendChild($dom->createCDATASection($this->description));
        $item->appendChild($description);

        // Create the <content_encoded>
        $content_encoded = $dom->createElement("content:encoded");
        $content_encoded->appendChild($dom->createCDATASection($this->description));
        $item->appendChild($content_encoded);

        // Create the <pubDate>
        $pubDate = $dom->createElement("pubDate", $this->pubDate->format(DATE_RFC2822));
        $item->appendChild($pubDate);


        //Link
        $link = $dom->createElement('link');
        $link->appendChild($dom->createCDATASection($this->link));
        $item->appendChild($link);

        // Create the <enclosure>
        $enclosure = $dom->createElement("enclosure");
        $enclosure->setAttribute("url", $this->url);
        $enclosure->setAttribute("type", $this->type);
        $enclosure->setAttribute("length", $this->length);
        $item->appendChild($enclosure);

        // Create the author
        if ($this->author) {
            // Create the <author>
            $author = $dom->createElement("author", $this->author);
            $item->appendChild($author);

            // Create the <itunes:author>
            $itune_author = $dom->createElement("itunes:author", $this->author);
            $item->appendChild($itune_author);
        }

        if ($this->season > 0) {
            $season = $dom->createElement("itunes:season", intval($this->season));
            $item->appendChild($season);
        }
        if ($this->episode > 0) {
            $episode = $dom->createElement("itunes:episode", intval($this->episode));
            $item->appendChild($episode);
        }
        if(!empty($this->episode_type)) {
            // Create the <episodeType>
            $episodeType = $dom->createElement("itunes:episodeType", $this->episode_type);
            $item->appendChild($episodeType);
        }

        // Create the <itunes:duration>
        $itune_duration = $dom->createElement("itunes:duration", $this->duration);
        $item->appendChild($itune_duration);

        // Create the <itunes:explicit>
        $explicit = $dom->createElement("itunes:explicit", (is_null($this->explicit) OR !$this->explicit OR empty($this->explicit) OR $this->explicit == 'no') ? 'false' : 'true');
        $item->appendChild($explicit);

        // Create the <guid>
        $guid = $dom->createElement("guid");
        $guid->appendChild($dom->createCDATASection($this->guid));
        $guid->setAttribute('isPermaLink','false');
        $item->appendChild($guid);

        // Create the <itunes:image>
        if ($this->image) {
            $itune_image = $dom->createElement("itunes:image");
            $itune_image->setAttribute("href", $this->image);
            $item->appendChild($itune_image);
        }
    }
}
