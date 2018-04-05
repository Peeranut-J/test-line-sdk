<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace LINE\LINEBot\KitchenSink\EventHandler\MessageHandler;

use LINE\LINEBot;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\KitchenSink\EventHandler;
use LINE\LINEBot\KitchenSink\EventHandler\MessageHandler\Util\UrlBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;

class TextMessageHandler implements EventHandler
{
    /** @var LINEBot $bot */
    private $bot;
    /** @var \Monolog\Logger $logger */
    private $logger;
    /** @var \Slim\Http\Request $logger */
    private $req;
    /** @var TextMessage $textMessage */
    private $textMessage;

    /**
     * TextMessageHandler constructor.
     * @param $bot
     * @param $logger
     * @param \Slim\Http\Request $req
     * @param TextMessage $textMessage
     */
    public function __construct($bot, $logger, \Slim\Http\Request $req, TextMessage $textMessage)
    {
        $this->bot = $bot;
        $this->logger = $logger;
        $this->req = $req;
        $this->textMessage = $textMessage;
    }

    public function handle()
    {
        $text = $this->textMessage->getText();
        $replyToken = $this->textMessage->getReplyToken();
        $this->logger->info("Got text message from $replyToken: $text");

        switch ($text) {
            case 'profile':
                $userId = $this->textMessage->getUserId();
                $this->sendProfile($replyToken, $userId);
                break;
            case 'bye':
                if ($this->textMessage->isRoomEvent()) {
                    $this->bot->replyText($replyToken, 'Leaving room');
                    $this->bot->leaveRoom($this->textMessage->getRoomId());
                    break;
                }
                if ($this->textMessage->isGroupEvent()) {
                    $this->bot->replyText($replyToken, 'Leaving group');
                    $this->bot->leaveGroup($this->textMessage->getGroupId());
                    break;
                }
                $this->bot->replyText($replyToken, 'Bot cannot leave from 1:1 chat');
                break;
            case 'confirm':
                $this->bot->replyMessage(
                    $replyToken,
                    new TemplateMessageBuilder(
                        'Confirm alt text',
                        new ConfirmTemplateBuilder('Do it?', [
                            new MessageTemplateActionBuilder('Yes', 'Yes!'),
                            new MessageTemplateActionBuilder('No', 'No!'),
                        ])
                    )
                );
                break;
            case 'buttons':
                $imageUrl = UrlBuilder::buildUrl($this->req, ['static', 'buttons', '1040.jpg']);
                $buttonTemplateBuilder = new ButtonTemplateBuilder(
                    'My button sample',
                    'Hello my button',
                    $imageUrl,
                    [
                        new UriTemplateActionBuilder('Go to line.me', 'https://line.me'),
                        new PostbackTemplateActionBuilder('Buy', 'action=buy&itemid=123'),
                        new PostbackTemplateActionBuilder('Add to cart', 'action=add&itemid=123'),
                        new MessageTemplateActionBuilder('Say message', 'hello hello'),
                    ]
                );
                $templateMessage = new TemplateMessageBuilder('Button alt text', $buttonTemplateBuilder);
                $this->bot->replyMessage($replyToken, $templateMessage);
                break;
            case 'carousel':
                $imageUrl = UrlBuilder::buildUrl($this->req, ['static', 'buttons', '1040.jpg']);
                $carouselTemplateBuilder = new CarouselTemplateBuilder([
                    new CarouselColumnTemplateBuilder('foo', 'bar', $imageUrl, [
                        new UriTemplateActionBuilder('Go to line.me', 'https://line.me'),
                        new PostbackTemplateActionBuilder('Buy', 'action=buy&itemid=123'),
                    ]),
                    new CarouselColumnTemplateBuilder('buz', 'qux', $imageUrl, [
                        new PostbackTemplateActionBuilder('Add to cart', 'action=add&itemid=123'),
                        new MessageTemplateActionBuilder('Say message', 'hello hello'),
                    ]),
                ]);
                $templateMessage = new TemplateMessageBuilder('Button alt text', $carouselTemplateBuilder);
                $this->bot->replyMessage($replyToken, $templateMessage);
                break;
            case 'imagemap':
                $richMessageUrl = UrlBuilder::buildUrl($this->req, ['static', 'rich']);
                $imagemapMessageBuilder = new ImagemapMessageBuilder(
                    $richMessageUrl,
                    'This is alt text',
                    new BaseSizeBuilder(1040, 1040),
                    [
                        new ImagemapUriActionBuilder(
                            'https://store.line.me/family/manga/en',
                            new AreaBuilder(0, 0, 520, 520)
                        ),
                        new ImagemapUriActionBuilder(
                            'https://store.line.me/family/music/en',
                            new AreaBuilder(520, 0, 520, 520)
                        ),
                        new ImagemapUriActionBuilder(
                            'https://store.line.me/family/play/en',
                            new AreaBuilder(0, 520, 520, 520)
                        ),
                        new ImagemapMessageActionBuilder(
                            'URANAI!',
                            new AreaBuilder(520, 520, 520, 520)
                        )
                    ]
                );
                $this->bot->replyMessage($replyToken, $imagemapMessageBuilder);
                break;
			case 'ขอดูผลการวินิจฉัย':
				$this->bot->replyText($replyToken, 'ขณะนี้ ผลการวินิจฉัย ต้องให้แพทย์เป็นผู้ส่งไปทางอีเมลล์ที่ได้ลงทะเบียนไว้');
                break;
			case 'ขอรายชื่อโรงพยาบาลที่เกี่ยวข้อง':
				$this->bot->replyText($replyToken, 'โรงพยาบาล A เบอร์ติดต่อ 02-000-0000                   โรงพยาบาล B เบอร์ติดต่อ 02-111-1111                       โรงพยาบาล C เบอร์ติดต่อ 02-456-8795                   โรงพยาบาล D เบอร์ติดต่อ 02-789-4561');
                break;
			case 'ปัจจัยเสี่ยงของต้อหิน':
				$this->bot->replyText($replyToken, 'ปัจจัยเสี่ยงของโรคต้อหินนั้น จะอบ่งออกเป็น 2 ลักษณะ คือ ต้อหินเฉียบพลัน กับ ต้อหินเรื้องรัง                                          ต้อหินเฉียบพลันจะมีปัจจัยเสี่ยงโดยคร่าวๆ ดังนี้                                           1. เป็นผู้หญิง                                                                                     2. เป็นผู้มีเชื้อสายเอเซีย                                                              3. อายุมากกว่า40ปี                                                               4. มีสายตายาว                                                               5. ครอบครัวมีประวัติเคยเป็นโรคนี้                                                               ต้อหินเรื้อรังจะมีปัจจัยเสี่ยงโดยคร่าวๆ ดังนี้                                                              1. มีเชื้อสายแอฟริกัน                                                              2. เป็นโรคเรื้อรังบางประเภท เช่น โรคหัวใจ โรคความดันโลหิตสูง                                                               3. เป็นโรคเบาหวาน                                                               4. มีสายตาสั้น                                                               5. ครอบครัวมีประวัติเคยเป็นโรคนี้                                                               6. ความดันลูกตาสูงผิดปกติ                                                               7. กระจกตาบางกว่าปกติ                                                               8. เคยได้รับการผ่าตัดดวงตา                                                               9. เคยได้รับการรักษาโรคเรื้อรังทางดวงตา                                                               10. เคยได้รับอุบัติเหตุทางตา                                                               11. เคยมีประวัติการใช้งานยาหยอดตาและยารับประทานบางชนิด โดยเฉพาะยาสเตียรอยด์                                                                ข้อมูลเพิ่มเติม : https://medthai.com/ต้อหิน/                                                                อ้างอิง : https://medthai.com/ต้อหิน/');
				break;
			case 'ขอทราบวิธีการใช้งาน App':
				$this->bot->replyText($replyToken, 'ในการใช้งาน Glaucoma checker bot นั้น มีวิธีใช้งาน ดังนี้                                     1.หากต้องการตรวจสอบเบื้องต้นว่าเป็นโรคต้อหินหรือไม่ กรุณาถ่ายภาพดวงตาของท่านด้วยอุปกรณ์ แล้วอัพโหลดรูปลงในไลน์บอทนี้                                     2. หากบอทได้ตอบกลับว่า "มีโอกาสเป็นโรค" หรือ "เป็นโรค" กรุณากดที่ปุ่ม "ขอรับ link กรอกข้อมูล" เพื่อที่บอทจะดำเนินการส่ง link google form สำหรับกรอกข้อมูลให้กับท่าน                                          3. หากต้องการทราบรายชื่อและเบอร์ติดต่อโรงพยาบาลที่เกี่ยวข้อง กรุณากดที่ปุ่ม "โรงพยาบาลที่เกี่ยวข้อง"                                     4. หากต้องการรายละเอียดปัจจัยเสี่ยงของต้อหิน กรุณากดที่ปุ่ม "ปัจจัยเสี่ยงของต้อหิน"');
				break;
            default:
                //$this->echoBack($replyToken, $text);
				$this->bot->replyText($replyToken, 'หากมีคำถาม หรือต้องการใช้บริการอะไร กรุณากดปุ่มใน App Menu หรือหากต้องการตรวจต้อหินเบื้องต้น กรุณาส่งรูปภาพ ขอบคุณครับ');
                break;
        }
    }

    /**
     * @param string $replyToken
     * @param string $text
     */
    private function echoBack($replyToken, $text)
    {
        $this->logger->info("Returns echo message $replyToken: $text");
        $this->bot->replyText($replyToken, $text);
    }

    private function sendProfile($replyToken, $userId)
    {
        if (!isset($userId)) {
            $this->bot->replyText($replyToken, "Bot can't use profile API without user ID");
            return;
        }

        $response = $this->bot->getProfile($userId);
        if (!$response->isSucceeded()) {
            $this->bot->replyText($replyToken, $response->getRawBody());
            return;
        }

        $profile = $response->getJSONDecodedBody();
        $this->bot->replyText(
            $replyToken,
            'Display name: ' . $profile['displayName'],
            'Status message: ' . $profile['statusMessage']
        );
    }
}
