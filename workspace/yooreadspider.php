<?php
/**
 * Created by PhpStorm.
 * User: XYX
 * Date: 2018/9/27
 * Time: 15:51
 */
namespace FishRead\Spider;
require __DIR__.'/../vendor/autoload.php';

use QL\QueryList;
use QL\Ext\AbsoluteUrl;

class YooReadSpider{
  protected $ql;
  protected $page;
  public function __construct($page)
  {
    // 注册插件
    $this->ql = QueryList::getInstance();
    $this->ql->use([
      AbsoluteUrl::class // 转换URL相对路径到绝对路径
    ]);
    $this->page = $page;
  }
  //获取章节编号
  protected function getNumber($html){
    $start = strrpos($html,"/")+1;
    $end = strrpos($html,".");
    $ok = substr($html,$start,$end-$start);
    return $ok;
  }
  //获取进度条
  function show_status($done, $total, $doing, $show, $size=50) {

    static $start_time;

    // if we go over our bound, just ignore it
    if($done > $total) return;

    if(empty($start_time)) $start_time=time();
    $now = time();

    $perc=(double)($done/$total);

    $bar=floor($perc*$size);

    $status_bar="\r[";
    $status_bar.=str_repeat("=", $bar);
    if($bar<$size){
      $status_bar.=">";
      $status_bar.=str_repeat(" ", $size-$bar);
    } else {
      $status_bar.="=";
    }

    $disp=number_format($perc*100, 0);

    $status_bar.="] $disp%  $done/$total";

    $rate = ($now-$start_time)/$done;
    $left = $total - $done;
    $eta = round($rate * $left, 2);

    $elapsed = $now - $start_time;

    $status_bar.= $doing." 剩余: ".number_format($eta)." sec.  已进行: ".number_format($elapsed)." sec.";

    echo "$status_bar  ";

    flush();

    // when done, send a newline
    if($done == $total) {
      echo "\n";
      echo "$show\n";
    }
  }
  //获取书籍的名字
  public function getBookInfo(){
    $book = $this->ql->get($this->page);
    //获取目录
    $bookName =$book->rules([
      'name'=>['.txt>h1', 'text'],
      'img'=>['.img>img', 'src']
    ])->absoluteUrl('https://www.yooread.com')
      ->query(function($item){
        return ['name'=>$item['name'],
                'img'=>$item['img']];
      })->getData()->all();
    return $bookName;
  }
  //获取书籍数据数组格式
  public function getBookArray(){
    $book = $this->ql->get($this->page);
    //获取目录
    $bigTitleData =$book->rules([
      'bigtitle'=>['table>tr>th', 'text', '-a']
    ])
      ->query(function($item, $key=0) use ($book){
        $href = $book->absoluteUrl('https://www.yooread.com')->rules([
          'href'=>['table:eq('.$key.')>tr>td>ul>li>a', 'href'],
        ])->query()->getData()->all();
        $key++;
        $hrefList = [];
        foreach($href as $value){
          $hrefList[] =  $value['href'];
        }
        return [$key.".".$item['bigtitle']=>$hrefList];
      })->getData()->all();
    $readList = [];
    $bookNum = 0;
    $nowNum = 1;
    foreach($bigTitleData as $content){
      $readList = array_merge($readList,$content);
      $bookNum=$bookNum+(count($content,1)-1);
    }
    //获取内容
    foreach($readList as $key=>$postList){
      $readList[$key] = [];
      foreach($postList as $otherKey=>$post){
        $this->show_status($nowNum,$bookNum,'获取书籍目录成功，开始尝试读取','读取完成，写入中...');
        $nowNum++;
        $postSpider =$this->ql->get($post);
        $title = $postSpider->rules([
          'title'=>['.main>h1','text'],
        ])
          ->query(function($item){
            return $item['title'];
          })
          ->getData()->all();
        $content = $postSpider
          ->rules([
            'content'=>['.main>p','text'],
          ])
          ->query(function($item){
            return $item['content'];
          })
          ->getData()->all();
        $readList[$key]=array_merge($readList[$key],[$title[0]=>[
          'href'=>$post,
          'title'=>$title[0],
          'content'=>implode(PHP_EOL.PHP_EOL,$content)
        ]]);
      }
      usort($readList[$key],function ($a, $b) {
        return $this->getNumber($a['href']) - $this->getNumber($b['href']);
      });
    }
    return $readList;
  }
  //静态方法生产一本书的md
  static function makeBook($page){
    $book  = new self($page);
    $bookName = $book->getBookInfo();
    echo $bookName[0]['name'] ;
    $bookData = $book->getBookArray();
    try{
      $book->writeFile($bookData,$bookName[0]['name'],$bookName[0]['img']);
    }catch (\Exception $e){
      echo $e->getMessage();
    }
    return $bookData;
  }
  //静态方法保存一本书的md
  static function saveBook($page){
    $book  = new self($page);
    $bookName = $book->getBookInfo();
    echo $bookName[0]['name'];
    $bookData = $book->getBookArray();
    try{
      $book->writeFile($bookData,$bookName[0]['name'],$bookName[0]['img']);
    }catch (\Exception $e){
      echo $e->getMessage();
    }
    return $bookData;
  }
  public function writeFile($bookData, $bookName, $bookImg){
    $bookName = str_replace(['/','//',':','*','"','<','>','|','?'],'_',$bookName);
    $bookFile = fopen("./book/$bookName.md", "w");
    fwrite($bookFile, "# ".$bookName.PHP_EOL.PHP_EOL);
    fwrite($bookFile, '!['.$this->getNumber($bookImg).']('.$bookImg.')'.PHP_EOL.PHP_EOL);
    foreach ($bookData as $key=>$numList){
      fwrite($bookFile, "## ".$key.PHP_EOL.PHP_EOL);
      foreach($numList as $content){
        fwrite($bookFile, "### ".$content['title'].PHP_EOL.PHP_EOL);
        fwrite($bookFile, $content['content'].PHP_EOL.PHP_EOL);
      }
    }
    fclose($bookFile);
  }
}


