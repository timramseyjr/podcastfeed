<?php

namespace Torann\PodcastFeed;

use DateTime;

class Manager
{
    /**
     * Package Config
     *
     * @var array
     */
    protected $config = [];

    /**
     * General title of the podcast
     *
     * @var string
     */
    private $title;

    /**
     * Subtitle of the podcast.
     *
     * @var string|null
     */
    private $subtitle;

    /**
     * Description of the podcast.
     *
     * @var string
     */
    private $description;

    /**
     * URL to the podcast website.
     *
     * @var string
     */
    private $link;

    /**
     * URL to the image representing the podcast.
     *
     * @var string
     */
    private $image;

    /**
     * Author of the podcast.
     *
     * @var string
     */
    private $author;

    /**
     * Categories of the podcast.
     *
     * @var array
     */
    private $categories = [];

    /**
     * Explicit flag of the podcast.
     *
     * @var string
     */
    private $explicit = false;

    /**
     * Language of the podcast.
     *
     * @var string
     */
    private $language = null;

    /**
     * Date of the last publication of the podcast.
     *
     * @var DateTime
     */
    private $pubDate;

    /**
     * Email address of the owner of the podcast.
     *
     * @var string
     */
    private $email = null;

    /**
     * Copyright podcast.
     *
     * @var string
     */
    private $copyright = null;

    /**
     * List of media for the podcast.
     *
     * @var array
     */
    private $media = [];

    /**
     * Locked status of the podcast.
     *
     * @var string
     */
    private $locked;

    /**
     * Class constructor.
     *
     * @param  array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // Set default headers
        $this->setHeader([]);
    }

    /**
     * Set the header of the podcast feed
     *
     * @param mixed $data
     */
    public function setHeader($data)
    {
        // Required
        $this->title = $this->getValue($data, 'title');
        $this->pubDate = $this->getValue($data, 'pubDate');
        $this->feed_type = $this->getValue($data, 'feed_type');
        $this->description = $this->getValue($data, 'description');
        $this->link = $this->getValue($data, 'link');
        $this->image = $this->getValue($data, 'image');
        $this->author = $this->getValue($data, 'author');
        $this->pubDate = $this->getValue($data, 'pubDate');
        $this->categories = $this->getValue($data, 'categories');

        // Optional values
        $this->explicit = $this->getValue($data, 'explicit');
        $this->subtitle = $this->getValue($data, 'subtitle');
        $this->language = $this->getValue($data, 'language');
        $this->email = $this->getValue($data, 'email');
        $this->copyright = $this->getValue($data, 'copyright');

        $this->locked = $this->getValue($data, 'locked');
    }

    /**
     * Get value from data and escape it.
     *
     * @param  mixed  $data
     * @param  string $key
     *
     * @return mixed
     */
    public function getValue($data, $key)
    {
        $value = array_get($data, $key, $this->getDefault($key));

        // Avoid escaping categories to confort to the itunes spec
        if($key == 'categories') {
            return $value;
        }

        return htmlspecialchars($value);
    }

    /**
     * Add media to the podcast feed.
     *
     * @param array $media
     */
    public function addMedia(array $media)
    {
        $this->media[] = new Media($media);
    }

    /**
     * Returns the podcast generated as character strings
     *
     * @return  string
     */
    public function toString()
    {
        return $this->generate()->saveXML();
    }

    /**
     * Returns the podcast generated as DOM document
     *
     * @return  \DOMDocument
     */
    public function toDom()
    {
        return $this->generate();
    }

    /**
     * Get default value from config
     *
     * @param  string $key
     * @param  mixed  $fallback
     *
     * @return mixed
     */
    public function getDefault($key, $fallback = null)
    {
        return array_get($this->config['defaults'], $key, $fallback);
    }

    /**
     * Generate the DOM document
     *
     * @return \DOMDocument
     */
    private function generate()
    {
        // Create the DOM
        $dom = new \DOMDocument("1.0", "utf-8");

        // Create the <rss>
        $rss = $dom->createElement("rss");
        $rss->setAttribute("xmlns:itunes", "http://www.itunes.com/dtds/podcast-1.0.dtd");
        $rss->setAttribute("xmlns:atom", "http://www.w3.org/2005/Atom");
        $rss->setAttribute("xmlns:cc", "http://web.resource.org/cc/");
        $rss->setAttribute("xmlns:podcast", "https://podcastindex.org/namespace/1.0");
        $rss->setAttribute("xmlns:googleplay", "http://www.google.com/schemas/play-podcasts/1.0");
        $rss->setAttribute("xmlns:content", "http://purl.org/rss/1.0/modules/content/");
        $rss->setAttribute("xmlns:rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
        $rss->setAttribute("xmlns:media", "http://search.yahoo.com/mrss/");
        $rss->setAttribute("version", "2.0");
        $dom->appendChild($rss);

        // Create the <channel>
        $channel = $dom->createElement("channel");
        $rss->appendChild($channel);

        // Create the <title>
        $title = $dom->createElement("title", $this->title);
        $channel->appendChild($title);

        // Create the <itunes:subtitle>
        if ($this->subtitle != null) {
            $itune_subtitle = $dom->createElement("itunes:subtitle", $this->subtitle);
            $channel->appendChild($itune_subtitle);
        }

        // Create the <itunes:type>
        if (!empty($this->feed_type)
            && ($this->feed_type == 'serial'
                OR  $this->feed_type == 'episodic')) {
            $itune_type = $dom->createElement("itunes:type", $this->feed_type);
            $channel->appendChild($itune_type);
        }

        // Create the <link>
        $link = $dom->createElement("link", $this->link);
        $channel->appendChild($link);

        //Atom Link
        $atom_link = $dom->createElement("atom:link");
        $atom_link->setAttribute('href',$this->link.'/rss');
        $atom_link->setAttribute('rel','self');
        $atom_link->setAttribute('type','application/rss+xml');
        $channel->appendChild($atom_link);

        // Create the <description>
        $description = $dom->createElement("description");
        $description->appendChild($dom->createCDATASection($this->description));
        $channel->appendChild($description);

        // Create the <itunes:summary>
        $itune_summary = $dom->createElement("itunes:summary", $this->description);
        $channel->appendChild($itune_summary);

        // Create the <image>
        $image = $dom->createElement("image");
        $image->appendChild($title->cloneNode(true));
        $image->appendChild($link->cloneNode(true));
        $channel->appendChild($image);
        $image_url = $dom->createElement("url", $this->image);
        $image->appendChild($image_url);

        // Create the <itunes:image>
        $itune_image = $dom->createElement("itunes:image");
        $itune_image->setAttribute("href", $this->image);
        $channel->appendChild($itune_image);

        // Create the <itunes:author>
        $itune_author = $dom->createElement("itunes:author", $this->author);
        $channel->appendChild($itune_author);

        //Locked
        $locked = $dom->createElement("podcast:locked", $this->locked);
        $locked->setAttribute('owner', $this->email);
        $channel->appendChild($locked);

        // Create the <itunes:owner>
        $itune_owner = $dom->createElement("itunes:owner");
        $itune_owner_name = $dom->createElement("itunes:name", $this->author);
        $itune_owner->appendChild($itune_owner_name);
        if ($this->email != null) {
            $itune_owner_email = $dom->createElement("itunes:email", $this->email);
            $itune_owner->appendChild($itune_owner_email);

            //managingEditor
            $managing_editor = $dom->createElement("managingEditor",$this->email.' ('.$this->email.')');
            $channel->appendChild($managing_editor);
        }
        $channel->appendChild($itune_owner);

        // Create the <itunes:category>
        foreach ($this->categories as $category => $subcategories) {
            $node = $channel->appendChild($dom->createElement('itunes:category'));
            $node->setAttribute("text", $category);
            foreach ($subcategories as $subcategory => $subcategories) {
                if (is_array($subcategories)) {
                    $subnode = $node->appendChild($dom->createElement('itunes:category'));
                    $subnode->setAttribute("text", $subcategory);
                    foreach ($subcategories as $subsubcategory) {
                        $subsubnode = $subnode->appendChild($dom->createElement('itunes:category'));
                        $subsubnode->setAttribute("text", $subsubcategory);
                    }
                } else {
                    $subnode = $node->appendChild($dom->createElement('itunes:category'));
                    $subnode->setAttribute("text", $subcategories);
                }
            }
            $channel->appendChild($node);
        }
        // Create the <itunes:explicit>
        if ($this->explicit !== null) {
            $explicit = $dom->createElement("itunes:explicit", $this->explicit);
            $channel->appendChild($explicit);
        }


        // Create the <language>
        if ($this->language !== null) {
            $language = $dom->createElement("language", $this->language);
            $channel->appendChild($language);
        }

        // Create the <copyright>
        if ($this->copyright !== null) {
            $copyright = $dom->createElement("copyright", $this->copyright);
            $channel->appendChild($copyright);
        }

        // Create the <items>
        foreach ($this->media as $media) {
            // Addition of media in the dom
            $media->addToDom($dom);

            // Get the latest date media for <pubDate>
            if ($this->pubDate == null) {
                $this->pubDate = $media->getPubDate();
            }
            else {
                if ($this->pubDate < $media->getPubDate()) {
                    $this->pubDate = $media->getPubDate();
                }
            }
        }

        // Create the <pubDate>
        if ($this->pubDate == null) {
            $this->pubDate = new DateTime();
        }
        if (is_string($this->pubDate)) {
            $this->pubDate = new DateTime($this->pubDate);
        }
        $pubDate = $dom->createElement("pubDate", $this->pubDate->format(DATE_RFC2822));
        $channel->appendChild($pubDate);
        $lastBuildDate = $dom->createElement("lastBuildDate", $this->pubDate->format(DATE_RFC2822));
        $channel->appendChild($lastBuildDate);

        // Return the DOM
        return $dom;
    }
}
