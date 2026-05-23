<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiprovider_openai;

use core\http_client;
use core_ai\ai_image;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class process image generation.
 *
 * @package    aiprovider_openai
 * @copyright  2024 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_generate_image extends abstract_processor {
    /** @var int The number of images to generate dall-e-3 only supports 1. */
    private int $numberimages = 1;

    /** @var string Response format: url or b64_json. */
    private string $responseformat = 'url';

    #[\Override]
    protected function query_ai_api(): array {
        $response = parent::query_ai_api();

        // If the request was successful, save the URL to a file.
        if ($response['success']) {
            $fileobj = $this->url_to_file(
                $this->action->get_configuration('userid'),
                $response['sourceurl']
            );
            // Add the file to the response, so the calling placement can do whatever they want with it.
            $response['draftfile'] = $fileobj;
        }

        return $response;
    }

    /**
     * Convert the given aspect ratio to an image size
     * that is compatible with the OpenAI API.
     *
     * @param string $ratio The aspect ratio of the image.
     * @return string The size of the image.
     */
    private function calculate_size(string $ratio): string {
        if ($ratio === 'square') {
            $size = '1024x1024';
        } else if ($ratio === 'landscape') {
            $size = '1792x1024';
        } else if ($ratio === 'portrait') {
            $size = '1024x1792';
        } else {
            throw new \coding_exception('Invalid aspect ratio: ' . $ratio);
        }
        return $size;
    }

    #[\Override]
    protected function create_request_object(string $userid): RequestInterface {
        // Create the request object.
        $requestobj = new \stdClass();
        $requestobj->model = $this->get_model();
        $requestobj->user = $userid;
        $requestobj->prompt = $this->action->get_configuration('prompttext');
        $requestobj->n = $this->numberimages;
        $requestobj->quality = $this->action->get_configuration('quality');
        $requestobj->response_format = $this->responseformat;
        $requestobj->size = $this->calculate_size($this->action->get_configuration('aspectratio'));
        $requestobj->style = $this->action->get_configuration('style');
        // Append the extra model settings.
        $modelsettings = $this->get_model_settings();
        foreach ($modelsettings as $setting => $value) {
            $requestobj->$setting = $value;
        }
        return new Request(
            method: 'POST',
            uri: '',
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: json_encode($requestobj),
        );
    }

    #[\Override]
    protected function handle_api_success(ResponseInterface $response): array {
        $responsebody = $response->getBody();
        $bodyobj = json_decode($responsebody->getContents());

        return [
            'success' => true,
            'sourceurl' => $this->normalise_image_url($bodyobj->data[0]->url),
            'revisedprompt' => $bodyobj->data[0]->revised_prompt ?? $this->action->get_configuration('prompttext'),
            'model' => $this->get_model(), // There is no model in the response, use config.
        ];
    }

    /**
     * Normalise image URLs returned by OpenAI-compatible providers.
     *
     * Some providers return HTML-escaped query strings in JSON responses.
     *
     * @param string $url The URL to normalise.
     * @return string
     */
    private function normalise_image_url(string $url): string {
        return html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
    }

    /**
     * Check whether the image URL is served by Pollinations.
     *
     * @param string $url The image URL.
     * @return bool
     */
    private function is_pollinations_url(string $url): bool {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === false || $host === null) {
            return false;
        }

        $host = strtolower($host);
        return $host === 'gen.pollinations.ai' || str_ends_with($host, '.pollinations.ai');
    }

    /**
     * Get the filename to use when storing the downloaded image.
     *
     * @param string $url The image URL.
     * @return string
     */
    private function get_image_filename(string $url): string {
        $path = parse_url($url, PHP_URL_PATH);
        $filename = $path ? basename($path) : '';
        $filename = clean_param(rawurldecode($filename), PARAM_FILE);

        if ($filename === '' || pathinfo($filename, PATHINFO_EXTENSION) === '') {
            return 'generated-image.png';
        }

        return $filename;
    }

    /**
     * Get Guzzle options for downloading the generated image.
     *
     * Pollinations protects generated image URLs with the same Bearer token as the
     * OpenAI-compatible generation endpoint, so the download request needs auth too.
     *
     * @param string $url The image URL.
     * @param string $tempdst The temporary destination path.
     * @return array
     */
    private function get_image_download_options(string $url, string $tempdst): array {
        global $CFG;

        $options = [
            'sink' => $tempdst,
            'timeout' => $CFG->repositorygetfiletimeout,
        ];

        if ($this->is_pollinations_url($url) && !empty($this->provider->config['apikey'])) {
            $options['headers'] = [
                'Authorization' => "Bearer {$this->provider->config['apikey']}",
            ];
        }

        return $options;
    }

    /**
     * Convert the url for the image to a file.
     *
     * Placements can't interact with the provider AI directly,
     * therefore we need to provide the image file in a format that can
     * be used by placements. So we use the file API.
     *
     * @param int $userid The user id.
     * @param string $url The URL to the image.
     * @return \stored_file The file object.
     */
    private function url_to_file(int $userid, string $url): \stored_file {
        global $CFG;

        require_once("{$CFG->libdir}/filelib.php");

        $url = $this->normalise_image_url($url);
        $filename = $this->get_image_filename($url);

        $client = \core\di::get(http_client::class);

        // Download the image and add the watermark.
        $tempdst = make_request_directory() . DIRECTORY_SEPARATOR . $filename;
        $client->get($url, $this->get_image_download_options($url, $tempdst));

        $image = new ai_image($tempdst);
        $image->add_watermark()->save();

        // We put the file in the user draft area initially.
        // Placements (on behalf of the user) can then move it to the correct location.
        $fileinfo = new \stdClass();
        $fileinfo->contextid = \context_user::instance($userid)->id;
        $fileinfo->filearea = 'draft';
        $fileinfo->component = 'user';
        $fileinfo->itemid = file_get_unused_draft_itemid();
        $fileinfo->filepath = '/';
        $fileinfo->filename = $filename;

        $fs = get_file_storage();
        return $fs->create_file_from_string($fileinfo, file_get_contents($tempdst));
    }
}
