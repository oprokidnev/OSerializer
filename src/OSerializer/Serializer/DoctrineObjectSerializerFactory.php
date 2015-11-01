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
 * and is licensed under the MIT license.
 */

namespace OSerializer\Serializer;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DoctrineObjectSerializerFactory implements FactoryInterface
{

    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        
        if($serviceLocator instanceof \Zend\Stdlib\Serializer\SerializerPluginManager){
            $serviceLocator = $serviceLocator->getServiceLocator();
        }
        if ($serviceLocator instanceof \Zend\ServiceManager\ServiceManager) {
            $serviceLocator = $serviceLocator;
        } else {
            $serviceLocator = $serviceLocator->getServiceLocator();
        }

        $options = (array) @$serviceLocator->get('Config')['o-serializer']['serializer'];

        $namingStrategy = isset($options['naming_strategy']) ? $options['naming_strategy']
                : null;
        if ($namingStrategy !== null) {
            $namingStrategy = $serviceLocator->get($options['naming_strategy']);
        }

        $formatters = isset($options['formatters']) ? $options['formatters'] : null;
        if ($formatters !== null) {
            foreach ($formatters as $key => &$formatter) {
                if (is_array($formatter)) {
                    
                } else {
                    $formatter = $serviceLocator->get($formatter);
                }
            }
        } else {
            $formatters = [];
        }

        $transformers = isset($options['transformers']) ? $options['transformers']
                : null;
        if ($transformers !== null) {
            foreach ($transformers as $key => &$transformer) {
                if (is_array($transformer)) {
                    
                } else {
                    $transformer = $serviceLocator->get($transformer);
                }
            }
        } else {
            $transformers = [];
        }

        $filters = isset($options['filters']) ? $options['filters'] : null;
        if ($filters !== null) {
            foreach ($filters as $key => &$filter) {
                if (is_array($filter)) {
                    
                } else {
                    $filter = $serviceLocator->get($filter);
                }
            }
        } else {
            \Doctrine\ORM\EntityManager::class;
            $filters = [];
        }
        if ($this->Serializer === null) {
            $this->Serializer = new DoctrineObjectSerializer($namingStrategy, $formatters, $filters, $transformers, 
                    $serviceLocator->get('doctrine.entitymanager.orm_default'), true);
        }
        return $this->Serializer;
    }

    protected $Serializer = null;

}
