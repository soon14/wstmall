<?php
namespace Home\Action;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.com 
 * 联系QQ:707563272
 * ============================================================================
 * 商品控制器
 */
use Think\Controller;
class GoodsAction extends BaseAction {
	/**
	 * 商品列表
	 * 
	 */
    public function getGoodsList(){
   		$mgoods = D('Home/Goods');
   		$mareas = D('Home/Areas');
   		$mcommunitys = D('Home/Communitys');
   		$mcommon = D('Home/Common');
   		
   		$areaId2 = $this->getDefaultCity();
   		$districts = $mareas->getDistricts($areaId2);
   		$obj["areaId2"] = $areaId2;
        $obj["areaId3"] = I("areaId3",0);
   		$communitys = $mcommunitys->queryByList($obj);

   		$this->assign('c1Id',I("c1Id"));
   		$this->assign('c2Id',I("c2Id"));
   		$this->assign('c3Id',I("c3Id"));
   		
   		$this->assign('msort',I("msort",0));
		$this->assign('sj',I("sj",0));
		$this->assign('stime',I("stime"));//上架开始时间
		$this->assign('etime',I("etime"));//上架结束时间
   		
   		$this->assign('areaId3',I("areaId3",0));
   		$this->assign('communityId',I("communityId",0));
   		
   		$pricelist = explode("_",I("prices"));
   		$this->assign('sprice',$pricelist[0]);
   		$this->assign('eprice',$pricelist[1]);
   		
   		$this->assign('brandId',I("brandId",0));
   		$this->assign('keyWords',I("keyWords"));
   		
   		
   		$rslist = $mgoods->getGoodsList($obj);
		$brands = $rslist["brands"];
		$pages = $rslist["pages"];
		
		//动态划分价格区间
		$maxPrice = $mgoods->getMaxPrice($obj);
		
		$minPrice = 0;
		$pavg5 = ($maxPrice/5);
		$prices = array();
    	$price_grade = 0.0001;
        for($i=-2; $i<= log10($maxPrice); $i++){
            $price_grade *= 10;
        }
    	//区间跨度
        $span = ceil(($maxPrice - $minPrice) / 8 / $price_grade) * $price_grade;
        if($span == 0){
            $span = $price_grade;
        }
		for($i=1;$i<=8;$i++){
			$prices[($i-1)*$span."_".($span * $i)] = ($i-1)*$span."-".($span * $i);
			if(($span * $i)>$maxPrice) break;
		}
		if(count($prices)<5){
			$prices = array();
			$prices["0_100"] = "0-100";
			$prices["100_200"] = "100-200";
			$prices["200_300"] = "200-300";
			$prices["300_400"] = "300-400";
			$prices["400_500"] = "400-500";
		}

		$this->assign('brands',$brands);
		$this->assign('pages',$pages);
		$this->assign('prices',$prices);
		$priceId = $prices[I("prices")];
		$this->assign('priceId',(strlen($priceId)>1)?I("prices"):'');
		
   		$this->assign('districts',$districts);
   		$this->assign('communitys',$communitys);
   		
   		$this->assign('goodsList',$rslist);
   		$this->display('default/goods_list');
    }
    
  
	/**
	 * 查询商品详情
	 * 
	 */
	public function getGoodsDetails(){
		$goods = D('Home/Goods');
		$kcode = I("kcode");
		$scrictCode = base64_encode(md5("wstmall".date("Y-m-d")));
		
		//查询商品详情		
		$goodsId = I("goodsId");
		$this->assign('goodsId',$goodsId);
		$obj["goodsId"] = $goodsId;	
		$goodsDetails = $goods->getGoodsDetails($obj);
		if($kcode==$scrictCode || ($goodsDetails["isSale"]==1 && $goodsDetails["goodsStatus"]==1)){
			if($kcode==$scrictCode){//来自后台管理员
				$this->assign('comefrom',1);
			}
			
			$shopServiceStatus = 1;
			if($goodsDetails["shopAtive"]==0){
				$shopServiceStatus = 0;
			}
			$goodsDetails["shopServiceStatus"] = $shopServiceStatus;
			$goodsDetails['goodsDesc'] = htmlspecialchars_decode($goodsDetails['goodsDesc']);
			$this->assign("goodsDetails",$goodsDetails);
			
			$areas = D('Home/Areas');
			$shopId = intval($goodsDetails["shopId"]);
			$obj["shopId"] = $shopId;
			$obj["areaId2"] = $this->getDefaultCity();
			
			$shops = D('Home/Shops');
			$shopScores = $shops->getShopScores($obj);
			$this->assign("shopScores",$shopScores);
			
			$shopCity = $areas->getShopCity($obj);
			$this->assign("shopCity",$shopCity[0]);
			
			$shopCommunitys = $areas->getShopCommunitys($obj);
			$this->assign("shopCommunitys",json_encode($shopCommunitys));
			
			$goodsImgs = $goods->getGoodsImgs();
			$this->assign("goodsImgs",$goodsImgs);
			
			$hotgoods = $goods->getHotGoods($goodsDetails['shopId']);
			$this->assign("hotgoods",$hotgoods);
			
			$relatedGoods = $goods->getRelatedGoods($goodsId);
			$this->assign("relatedGoods",$relatedGoods);
			
			$this->display('default/goods_details');
		}else{
			$this->display('default/goods_notexist');
		}

	}
	
	/**
	 * 获取商品库存
	 * 
	 */
	public function getGoodsStock(){
		
		$goods = D('Home/Goods');
		$goodsStock = $goods->getGoodsStock();
		echo json_encode($goodsStock);
		
	}
	
	/**
	 * 获取服务社区
	 * 
	 */
	public function getServiceCommunitys(){
		
		$areas = D('Home/Areas');
		$serviceCommunitys = $areas->getShopCommunitys();
		echo json_encode($serviceCommunitys);
	}
	
   /**
	* 分页查询-出售中的商品
	*/
	public function queryOnSaleByPage(){
		$this->isShopLogin();
		//获取商家商品分类
		$m = D('Home/ShopsCats');
		$this->assign('shopCatsList',$m->queryByList($_SESSION['USER']['shopId'],0));
		$m = D('Home/Goods');
    	$page = $m->queryOnSaleByPage($_SESSION['USER']['shopId']);
    	$pager = new \Think\Page($page['total'],$page['pageSize']);
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign("umark","queryOnSaleByPage");
    	$this->assign("shopCatId2",I('shopCatId2'));
    	$this->assign("shopCatId1",I('shopCatId1'));
    	$this->assign("goodsName",I('goodsName'));
        $this->display("default/shops/goods/list_onsale");
	}
   /**
	* 分页查询-仓库中的商品
	*/
	public function queryUnSaleByPage(){
		$this->isShopLogin();
		//获取商家商品分类
		$m = D('Home/ShopsCats');
		$this->assign('shopCatsList',$m->queryByList($_SESSION['USER']['shopId'],0));
		$m = D('Home/Goods');
    	$page = $m->queryUnSaleByPage($_SESSION['USER']['shopId']);
    	$pager = new \Think\Page($page['total'],$page['pageSize']);
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign("umark","queryUnSaleByPage");
    	$this->assign("shopCatId2",I('shopCatId2'));
    	$this->assign("shopCatId1",I('shopCatId1'));
    	$this->assign("goodsName",I('goodsName'));
        $this->display("default/shops/goods/list_unsale");
	}
   /**
	* 分页查询-在审核中的商品
	*/
	public function queryPenddingByPage(){
		$this->isShopLogin();
		//获取商家商品分类
		$m = D('Home/ShopsCats');
		$this->assign('shopCatsList',$m->queryByList($_SESSION['USER']['shopId'],0));
		$m = D('Home/Goods');
		$m = D('Home/Goods');
    	$page = $m->queryPenddingByPage($_SESSION['USER']['shopId']);
    	$pager = new \Think\Page($page['total'],$page['pageSize']);
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign("umark","queryPenddingByPage");
    	$this->assign("shopCatId2",I('shopCatId2'));
    	$this->assign("shopCatId1",I('shopCatId1'));
    	$this->assign("goodsName",I('goodsName'));
        $this->display("default/shops/goods/list_pendding");
	}
	/**
	 * 跳到新增/编辑商品
	 */
    public function toEdit(){
		$this->isShopLogin();
		//获取品牌信息
		$m = D('Home/Brands');
		$this->assign('brandList',$m->queryByList());
		//获取商品分类信息
		$m = D('Home/GoodsCats');
		$this->assign('goodsCatsList',$m->queryByList());
		//获取商家商品分类
		$m = D('Home/ShopsCats');
		$this->assign('shopCatsList',$m->queryByList($_SESSION['USER']['shopId'],0));
		$m = D('Home/Goods');
		$object = array();
		
    	if(I('id',0)>0){
    		$object = $m->get();
    	}else{
    		$object = $m->getModel();
    	}
    	$this->assign('object',$object);
    	$this->assign("umark",I('umark'));
        $this->display("default/shops/goods/edit");
	}
	/**
	 * 新增商品
	 */
	public function edit(){
		$this->isShopLogin();
		$m = D('Home/Goods');
    	$rs = array();
    	if(I('id',0)>0){
    		$rs = $m->edit();
    	}else{
    		$rs = $m->add();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除商品
	 */
	public function del(){
		$this->isShopLogin();
		$m = D('Home/Goods');
		$rs = $m->del();
		$this->ajaxReturn($rs);
	}
	/**
	 * 批量设置商品状态
	 */
	public function goodsSet(){
		$this->isShopLogin();
		$m = D('Home/Goods');
		$rs = $m->goodsSet();
		$this->ajaxReturn($rs);
	}
	/**
	 * 批量删除商品
	 */
	public function batchDel(){
		$this->isShopLogin();
		$m = D('Home/Goods');
		$rs = $m->batchDel();
		$this->ajaxReturn($rs);
	}
	/**
	 * 修改商品上架/下架状态
	 */
	public function sale(){
		$this->isShopLogin();
		$m = D('Home/Goods');
		$rs = $m->sale();
		$this->ajaxReturn($rs);
	}
	/**
	 * 核对商品信息
	 */
	public function checkGoodsStock(){	
		
		$m = D('Home/Goods');
		$totalMoney = 0;
		$shopcart = $_SESSION["mycart"]?$_SESSION["mycart"]:array();	
		
		$catgoods = array();		
		foreach($shopcart as $key=>$cgoods){				
			$goods = $m->getGoodsInfo($key);
			if($goods["isBook"]==1){
				$goods["goodsStock"] = $goods["goodsStock"]+$goods["bookQuantity"];
			}
			$goods["cnt"] = $cgoods["cnt"];
			$totalMoney += $goods["cnt"]*$goods["shopPrice"];			
			$catgoods[] = $goods;
		}
		
		$this->ajaxReturn($catgoods);
	}
	
	public function getGoodsappraises(){	
		
		$goods = D('Home/Goods');
		
		$goodsAppraises = $goods->getGoodsAppraises();
		
		$this->ajaxReturn($goodsAppraises);
	}
	
	/**
	 * 获取验证码
	 */
	public function getGoodsVerify(){
		$data = array();
		$data["status"] = 1;
		$verifyCode = base64_encode(md5("wstmall".date("Y-m-d")));
		$data["verifyCode"] = $verifyCode;
		$this->ajaxReturn($data);
	}
	

	
}