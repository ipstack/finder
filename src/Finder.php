<?php

namespace Ipstack\Finder;

/**
 * Class Finder
 *
 * @const int FORMAT_VERSION
 * @property array    $meta
 * @property integer  $fileSize
 * @property resource $db
 */
class Finder
{

    /**
     * @const int Parser version.
     */
    const FORMAT_VERSION = 2;

    /**
     * Metadata.
     *
     * @var array
     */
    protected $meta=array();

    /**
     * Size of database file.
     *
     * @var int
     */
    protected $fileSize;

    /**
     * Database file handler.
     *
     * @var resource
     */
    protected $db;

    /**
     * Iptool constructor.
     *
     * @param string $databaseFile
     * @throws \InvalidArgumentException
     */
    public function __construct($databaseFile)
    {
        if (!is_readable($databaseFile)) {
            fclose($this->db);
            throw new \InvalidArgumentException('can not read database file');
        }
        $this->fileSize = filesize($databaseFile);
        $this->db = fopen($databaseFile, 'rb');

        $meta = unpack('A3control/Ssize', fread($this->db, 5));
        if ($meta['control'] !== 'ISD') {
            fclose($this->db);
            throw new \InvalidArgumentException('file is not IPStack database');
        }

        $header = fread($this->db, $meta['size']);
        $offset = 0;
        $meta += unpack('Cversion/CRGC/SRGF/SRGD/CRLC/CRLF/SRLD', substr($header,$offset,10));
        if ($meta['version'] !== self::FORMAT_VERSION) {
            fclose($this->db);
            throw new \InvalidArgumentException('file is not IPStack database version '.self::FORMAT_VERSION);
        }

        $offset += 10;
        $unpack = 'A'.$meta['RLF'].'RLUF/A'.$meta['RGF'].'RGMUF';
        $size = $meta['RLF']+$meta['RGF'];
        $meta += unpack($unpack, substr($header, $offset, $size));

        $this->meta['relations'] = array();
        $offset += $size;
        for ($i=0;$i<$meta['RLC'];$i++) {
            $relation = unpack(
                $meta['RLUF'],
                substr($header, $offset, $meta['RLD'])
            );
            $this->meta['relations'][$relation['p']][$relation['f']] = $relation['c'];
            $offset += $meta['RLD'];
        }
        for ($i=0;$i<$meta['RGC'];$i++) {
            $definion = unpack(
                $meta['RGMUF'],
                substr($header, $offset, $meta['RGD'])
            );
            $id = $definion['name'];
            unset($definion['name']);
            $this->meta['registers'][$id] = $definion;
            $offset += $meta['RGD'];
        }
        $definion = unpack(
            $meta['RGMUF'],
            substr($header, $offset, $meta['RGD'])
        );
        unset($definion['name']);
        $this->meta['networks'] = $definion;
        $offset += $meta['RGD'];
        $this->meta['index'] = array_values(unpack('I*',substr($header, $offset)));
        $offset += 1029;
        $this->meta['networks']['offset'] = $offset;
        $offset += $this->meta['networks']['items']*$this->meta['networks']['len'];

        foreach ($this->meta['registers'] as $id=>$register) {
            $this->meta['registers'][$id]['offset'] = $offset;
            $offset += ($register['items']+1)*$register['len'];
        }
    }

    public function __destruct()
    {
        if (is_resource($this->db)) fclose($this->db);
    }


    public function find($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
        $data = array();
        $octet = (int)$ip;
        $long = pack('N',ip2long($ip));
        $start = $this->meta['index'][$octet];
        $stop = $this->meta['index'][$octet];
        while($octet < 255 && $this->meta['index'][$octet] == $start) {
            $octet++;
            $stop = $this->meta['index'][$octet];
        }
        if ($stop == $start) {
            $stop = $this->meta['networks']['items'];
        } elseif ($stop < $this->meta['networks']['items']) {
            $stop++;
        }
        if ($start > 0) {
            $start--;
        }
        $blockCount = $stop-$start;
        $seek = $this->meta['networks']['offset']+($start*$this->meta['networks']['len']);
        fseek($this->db,$seek);
        $blocks = fread($this->db,$blockCount*$this->meta['networks']['len']);
        $start = 0;
        $stop = $blockCount;
        do {
            $center = ($start + $stop) >> 1;
            $sc = substr($blocks, $center * $this->meta['networks']['len'], 4);
            if ($sc > $long) {
                $stop = $center;
            } else {
                $start = $center;
            }
            $blocksCount = $stop - $start;
        } while ($blocksCount >= 2);
        if ($long > $stop) {
            $start = $stop;
        }
        $block = substr($blocks, $start * $this->meta['networks']['len'], $this->meta['networks']['len']);
        $network = unpack('Nfirst',substr($block,0,4));
        $data['network']['first'] = long2ip($network['first']);
        $next = substr($blocks,($start+1)*$this->meta['networks']['len'],4);
        if ($next) {
            $network = unpack('Nlast',$next);
            $data['network']['last'] = long2ip($network['last']-1);
        } else {
            $data['network']['last'] = '255.255.255.255';
        }
        $registers = unpack($this->meta['networks']['pack'],substr($block,4));
        foreach ($registers as $register=>$item) {
            $data['data'][$register] = $this->getRegisterRecord($register,$item);
        }
        foreach ($this->meta['relations'] as $parent=>$relations) {
            foreach ($relations as $field=>$child) {
                if (isset($data['data'][$parent][$field])) {
                    $data['data'][$child] = $this->getRegisterRecord($child, $data['data'][$parent][$field]);
                }
            }
        }
        return $data;
    }

    public function getRegisterRecord($register,$item)
    {
        if ($item > $this->meta['registers'][$register]['items']) $item = 0;
        $seek = $this->meta['registers'][$register]['offset']+($item * $this->meta['registers'][$register]['len']);
        fseek($this->db,$seek);
        $data = unpack($this->meta['registers'][$register]['pack'],fread($this->db,$this->meta['registers'][$register]['len']));
        return $data;
    }

    public function about()
    {
        $about = array(
            'created' => 0,
            'author' => '',
            'license' => '',
            'networks' => array(
                'count' => $this->meta['networks']['items'],
                'data' => array(),
            ),
        );
        $register = array('offset'=>0,'len'=>0,'items'=>0);
        foreach ($this->meta['registers'] as $r=>$register) {
            $about['networks']['data'][$r] = array_keys(unpack($register['pack'],str_pad('',$register['len'],' ')));
        }
        $offset = $register['offset'] + ($register['len'] * ($register['items']+1));
        fseek($this->db,$offset);
        $info = fread($this->db,$this->fileSize-$offset);
        $tmp = unpack('N1created/A128author/A*license', $info);
        $about = array_replace($about, $tmp);
        return $about;
    }

    /**
     * Return array of register rows
     *
     * @param $register
     * @return array
     */
    public function getRegister($register)
    {
        if (!isset($this->meta['registers'][$register])) return array();
        $result = array();
        $seek = $this->meta['registers'][$register]['offset']+$this->meta['registers'][$register]['len'];
        fseek($this->db,$seek);
        for($i = 1;$i<=$this->meta['registers'][$register]['items'];$i++) {
            $result[$i] = unpack($this->meta['registers'][$register]['pack'],fread($this->db,$this->meta['registers'][$register]['len']));
        }
        return $result;
    }
}
