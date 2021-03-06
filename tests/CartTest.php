<?php

namespace Cart;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;

use Cart\Storage\Store;

class CartTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIsArrayable()
    {
        $cart = $this->getCart();

        $this->assertInstanceOf(Arrayable::class, $cart);

        $item = new CartItem([
            'name' => 'foo',
        ]);

        $cart->add($item);

        $cartArr = $cart->toArray();

        $this->assertInternalType('array', $cartArr);
        $this->assertArrayHasKey('id', $cartArr);
        $this->assertArrayHasKey('items', $cartArr);
        $this->assertInternalType('array', $cartArr['items']);
    }

    public function testGetId()
    {
        $this->assertSame($this->getCart()->getId(), 'foo');
    }

    public function testGetStore()
    {
        $cart = $this->getCart();

        $store = $cart->getStore();

        $this->assertSame($store, Assert::readAttribute($cart, 'store'));
    }

    public function testAddItem()
    {
        $cart = $this->getCart();
        $item = new CartItem([
            'name' => 'foo',
        ]);

        // test adding a new item
        $cart->add($item);
        $cartItems = Assert::readAttribute($cart, 'items');

        $this->assertSame($cartItems[0], $item);

        // test adding the same item again just increases the quantity of the
        // existing item
        $cart->add($item);
        $cartItems = Assert::readAttribute($cart, 'items');

        $this->assertSame($cartItems[0]->quantity, 2);
    }

    public function testHasItem()
    {
        $cart = $this->getCart();
        $item = new CartItem([
            'name' => 'foo',
        ]);
        $itemId = $item->id;

        $cart->add($item);

        $this->assertTrue($cart->has($itemId));
        $this->assertFalse($cart->has('foo'));
    }

    public function testRemoveItem()
    {
        $cart = $this->getCart();
        $item = new CartItem([
            'name' => 'foo',
        ]);
        $itemId = $item->id;

        $cart->add($item);
        $this->assertTrue($cart->has($itemId));

        $cart->remove($itemId);
        $this->assertFalse($cart->has($itemId));
    }

    public function testGetItem()
    {
        $cart = $this->getCart();
        $item = new CartItem([
            'name' => 'foo',
        ]);
        $itemId = $item->id;

        $cart->add($item);

        $this->assertSame($cart->get($itemId), $item);
    }

    public function testUpdateItem()
    {
        $cart = $this->getCart();
        $item = new CartItem([
            'name' => 'foo',
        ]);
        $itemId = $item->id;

        $cart->add($item);
        $newId = $cart->update($itemId, 'name', 'bar');

        // test Cart::update returns items new id
        $cartItems = Assert::readAttribute($cart, 'items');
        $this->assertSame($newId, $cartItems[0]->id);

        // test updating a property of the item
        $updatedItem = $cart->get($newId);

        $this->assertSame($updatedItem->name, 'bar');

        // test trying to update a non existent item
        $this->expectException('InvalidArgumentException');
        $cart->update('foo', 'name', 'bar');
    }

    public function testTotalUniqueItems()
    {
        $cart = $this->getCart();

        $item1 = new CartItem([
            'name' => 'foo',
            'quantity' => 2,
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
            'quantity' => 1,
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $this->assertEquals(2, $cart->totalUniqueItems());
    }

    public function testTotalItems()
    {
        $cart = $this->getCart();

        $item1 = new CartItem([
            'name' => 'foo',
            'quantity' => 2,
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
            'quantity' => 1,
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $this->assertEquals(3, $cart->totalItems());
    }

    public function testAll()
    {
        $cart = $this->getCart();

        $item1 = new CartItem([
            'name' => 'foo',
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $cartItems = $cart->all();

        $this->assertInternalType('array', $cartItems);
        $this->assertSame($cartItems, Assert::readAttribute($cart, 'items'));
    }

    public function testClear()
    {
        $store = m::mock(Store::class);
        $store->shouldReceive('flush')->times(1);

        $cart = new Cart('foo', $store);

        $item1 = new CartItem([
            'name' => 'foo',
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $cart->clear();

        $cartItems = $cart->all();

        $this->assertCount(0, $cartItems);
    }

    public function testTotal()
    {
        $cart = $this->getCart();

        $item1 = new CartItem([
            'name' => 'foo',
            'price' => 10.00,
            'tax' => 5.00,
            'quantity' => 2,
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
            'price' => 5.00,
            'tax' => 1.00,
            'quantity' => 2,
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $total = $cart->total();

        $this->assertInternalType('float', $total);
        $this->assertSame($total, 42.00);
    }

    public function testTotalExcludingTax()
    {
        $cart = $this->getCart();

        $item1 = new CartItem([
            'name' => 'foo',
            'price' => 10.00,
            'tax' => 5.00,
            'quantity' => 2,
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
            'price' => 5.00,
            'tax' => 1.00,
            'quantity' => 2,
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $total = $cart->totalExcludingTax();

        $this->assertInternalType('float', $total);
        $this->assertSame($total, 30.00);
    }

    public function testTax()
    {
        $cart = $this->getCart();

        $item1 = new CartItem([
            'name' => 'foo',
            'price' => 10.00,
            'tax' => 5.00,
            'quantity' => 2,
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
            'price' => 5.00,
            'tax' => 1.00,
            'quantity' => 2,
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $tax = $cart->tax(false);

        $this->assertInternalType('float', $tax);
        $this->assertSame($tax, 12.00);
    }

    public function testSave()
    {
        $store = m::mock(Store::class);
        $store->shouldReceive('put')->times(1);

        $cart = new Cart('foo', $store);

        $cart->save();
    }

    public function testRestore()
    {
        $item1 = new CartItem([
            'name' => 'foo',
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
        ]);

        $storeGetReturn = [
            'id' => 'foo',
            'items' => [
                $item1->toArray(),
                $item2->toArray(),
            ],
        ];

        $store = m::mock(Store::class);
        $store
            ->shouldReceive('get')
            ->times(1)
            ->andReturn(serialize($storeGetReturn));

        $cart = new Cart('foo', $store);

        $cart->restore();

        $this->assertSame('foo', $cart->getId());
        $this->assertEquals($cart->totalUniqueItems(), 2);
        $this->assertTrue($cart->has($item1->id));
        $this->assertTrue($cart->has($item2->id));
    }

    public function testEmptyRestore()
    {
        $store = m::mock(Store::class);
        $store
            ->shouldReceive('get')
            ->times(1)
            ->andReturn('');

        $cart = new Cart('foo', $store);

        $cart->restore(); // should not throw exception
    }

    public function testRestoreExceptions()
    {
        $exceptionCounter = 0;

        $store = m::mock(Store::class);

        $store
            ->shouldReceive('get')
            ->times(6)
            ->andReturn(
                '!foo!', // unserializable
                serialize('bar'), // not array
                serialize(['id' => 'foo']), // missing items
                serialize(['items' => []]), // missing id
                serialize(['id' => [], 'items' => []]), // invalid id
                serialize(['items' => 'foo', 'id' => 'foo']) // invalid items
            );

        $cart = new Cart('foo', $store);

        for ($i = 1; $i <= 6; ++$i) {
            try {
                $cart->restore();
            } catch (CartRestoreException $e) {
                ++$exceptionCounter;
            }
        }

        $this->assertEquals($exceptionCounter, 6);
    }

    public function testFindItems()
    {
        $cart = $this->getCart();

        $item1 = new CartItem([
            'name' => 'foo',
            'quantity' => 2,
            'size' => 'XXL'
        ]);

        $item2 = new CartItem([
            'name' => 'bar',
            'quantity' => 1,
            'size' => 'S'
        ]);

        $cart->add($item1);
        $cart->add($item2);

        $items = $cart->findItems('size', 'S');
        $this->assertCount(1, $items);
        $this->assertEquals('S', $items[0]->size);
    }

    public function getCart()
    {
        $store = m::mock(Store::class);

        return new Cart('foo', $store);
    }
}
