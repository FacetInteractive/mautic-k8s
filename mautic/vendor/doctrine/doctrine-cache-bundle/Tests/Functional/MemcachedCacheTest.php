<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Bundle\DoctrineCacheBundle\Tests\Functional;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @group Functional
 * @group Memcached
 */
class MemcachedCacheTest extends BaseCacheTest
{
    public function testPersistentId()
    {
        $cache = $this->createCacheDriver();
        $this->assertEquals('app', $cache->getMemcached()->getPersistentId());
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        if ( ! extension_loaded('memcached')) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of memcached');
        }

        if (@fsockopen('localhost', 11211) === false) {
            $this->markTestSkipped('The ' . __CLASS__ .' cannot connect to memcached');
        }
    }

    protected function overrideContainer(ContainerBuilder $container)
    {
        $container->setParameter('doctrine_cache.memcached.connection.class', 'Doctrine\Bundle\DoctrineCacheBundle\Tests\Functional\Fixtures\Memcached');
    }

    /**
     * {@inheritDoc}
     */
    protected function createCacheDriver()
    {
        $container = $this->compileContainer('memcached');
        $cache     = $container->get('doctrine_cache.providers.my_memcached_cache');

        return $cache;
    }
}
