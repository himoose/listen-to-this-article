=== Listen to This Article as a Podcast ===
Contributors: himoose
Tags: text to speech, audio player, read aloud, accessibility, tts
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn your articles into podcast-style audio using natural AI narration, with a clean embedded player that improves accessibility and SEO/AEO.

== Description ==

**Listen to This Article as a Podcast** is the official WordPress plugin for [Hi, Moose text-to-podcast generator](https://himoose.com/podcast-generator). It allows you to effortlessly embed high-quality, AI-generated podcasts directly into your WordPress posts.

It adds an audio version of your posts—similar to a “listen to this article” or “read aloud” button—but powered by high-quality, natural AI voices.  
Perfect for sites that want to offer **text-to-speech**, **podcast-style audio**, or **article-to-audio** experiences without managing audio files manually.

By offering an audio version of your content, you can:
* **Increase Engagement:** Users stay longer when they can listen instead of read.
* **Improve Accessibility:** Great for readers with visual impairments or who prefer audio.
* **Boost SEO & AEO:** The plugin embeds full transcripts and Schema.org JSON-LD data. The conversational nature of the transcript is optimized for Answer Engine Optimization (AEO) and Generative Engine Optimization (GEO), helping your content perform better in AI-powered search results like ChatGPT, Gemini, and Perplexity.

This plugin automatically detects your domain, fetches your completed episodes from the Hi, Moose platform, and lets you insert them with a single click.

On the Hi, Moose platform, you can customize each podcast-style audio version before embedding it—choose narration voices, adjust pacing and length, provide optional focus instructions, and fine-tune the player’s colors. Hi, Moose also includes built-in analytics showing listeners, plays, resumes, and completion rates. Once generated, the final audio and transcript are embedded on your WordPress site through a clean, lightweight player.

= Features =

* **“Listen to This Article” Button:** Add an instant audio version of your post.
* **AI Text-to-Speech:** High-quality AI voices generate natural, podcast-style audio.
* **One-Click Embedding:** Choose an episode right from the post editor sidebar.
* **SEO-Friendly Transcript:** Embeds a full, indexable transcript for improved accessibility, SEO, and AEO/GEO visibility.
* **Schema.org JSON-LD:** Automatically includes PodcastEpisode structured data.
* **Flexible Placement:** Auto-insert at the top of a post or use the `[himoose_podcast]` shortcode.
* **Accessibility Friendly:** Ideal for sites offering read-aloud or audio alternatives.

== Service Disclosure ==

This plugin relies on the [Hi, Moose](https://himoose.com) platform to generate and host podcast audio.

*The audio player and transcript are delivered as an embed directly from the Hi, Moose platform, similar to how YouTube or Vimeo embeds work.*

* **Service:** Hi, Moose (AI Podcast Generator)
* **Data Sent:** Only your domain name is sent to our API to retrieve podcast episodes. No post content or user data is sent.
* **Analytics:** The embedded player tracks plays, pauses, and transcript interactions. IP addresses are hashed before storage. No personally identifiable information (PII) is collected.
* **Pricing & Free Tier:** Hi, Moose includes a free tier for WordPress users with 3 free podcast episodes and up to 10,000 monthly listens. Optional paid plans are available if you need more capacity, but this plugin will not show ads, nag banners, or restrict the WordPress editing experience.
* **Terms of Use:** [https://himoose.com/terms](https://himoose.com/terms)
* **Privacy Policy:** [https://himoose.com/privacy-policy](https://himoose.com/privacy-policy)

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/listen-to-this-article` directory, or install the plugin through the WordPress plugins screen.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Go to **Settings → Hi, Moose Podcast Generator** and enter your API Key from Hi, Moose. 

== Frequently Asked Questions ==

= Where do I get an API Key? =
You can generate an API key from your [Hi, Moose dashboard](https://app.himoose.com/register?source=wordpress).

= Can I define the API key in wp-config.php? =
Yes! For added security, you can define your API key in your `wp-config.php` file. Add the following line:
`define( 'HIMOOSE_API_KEY', 'your-api-key-here' );`
This will override any key saved in the database.

= Does this work with the Block Editor (Gutenberg)? =
Yes, the plugin adds a meta box to the document sidebar (under the "Post" tab) in the Block Editor, allowing you to select episodes easily.

= Can I place the player manually? =
Yes! Go to the settings page and uncheck "Auto Insert Player". Then, use the shortcode `[himoose_podcast]` anywhere in your post content.


== Screenshots ==

1. Podcast player embedded at the top of a blog post.
2. Selecting a generated podcast episode from the Classic Editor sidebar.
3. Selecting a generated podcast episode from the Block Editor sidebar.
4. Using the optional `[himoose_podcast]` shortcode.
5. Plugin settings page for entering your Hi, Moose API key.


== Changelog ==

= 1.0.0 =
*   Initial release.
