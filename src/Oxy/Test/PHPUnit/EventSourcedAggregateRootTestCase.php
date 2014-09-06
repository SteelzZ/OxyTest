<?php
abstract class Oxy_Test_PHPUnit_EventSourcedAggregateRootTestCase extends PHPUnit_Framework_TestCase
{    
    /**
     * Assert if collection contains required events
     * 
     * @param Oxy_EventStore_EventProvider_EventProviderInterface $changes
     * @param array $expected
     * @param boolean $outPutgeneratedEvents
     */
    public static function assertEvents(
        Oxy_EventStore_EventProvider_EventProviderInterface $aggregateRoot, 
        array $expected,
        $outputGeneratedEvents = false
    )
    {   
        if($outputGeneratedEvents){
            var_dump($aggregateRoot->getChanges());
            var_dump($aggregateRoot->getChanges()->count());
            var_dump(count($expected));
        }
        parent::assertEquals(count($expected), $aggregateRoot->getChanges()->count());
        foreach($aggregateRoot->getChanges() as $index => $event){
            if($event instanceof Oxy_EventStore_Event_StorableEventInterface){
                $currentExpected = array_shift($expected);                
                $eventName = get_class($event->getEvent());
                
                // We need to assert event data
                if(isset($currentExpected[$eventName]) && is_array($currentExpected[$eventName])){
                    parent::assertEquals(
                        array_shift(array_keys($currentExpected)), 
                        $eventName
                    );
                    
                    foreach($currentExpected[$eventName] as $property => $propertyValue){
                        $method = 'get' . ucfirst($property);
                        $realEvent = $event->getEvent();
                        
                        parent::assertEquals(
                            $propertyValue, 
                            $realEvent->$method()
                        );
                    }
                } else {
                    parent::assertEquals(
                        $currentExpected[0], 
                        $eventName
                    );
                }
            } else {
                throw new Oxy_Test_PHPUnit_Exception(
                    sprintf('Event must implement Oxy_EventStore_Event_StorableEventInterface interface')
                );
            }
        }     
    }
    
    /**
     * Assert if AR has required child entities and assert properties
     * 
     * @param Oxy_EventStore_EventProvider_EventProviderInterface $changes
     * @param array $expected
     */
    public static function assertChildEntities(
        Oxy_EventStore_EventProvider_EventProviderInterface $aggregateRoot, 
        array $expected
    )
    {   
        parent::assertEquals(count($expected), $aggregateRoot->getChildEntities()->count());
        foreach($aggregateRoot->getChildEntities() as $entityGuid => $entity){
            if($entity instanceof Oxy_Domain_AggregateRoot_ChildEntityInterface){
                $entityClass = get_class($entity);
                parent::assertContains($entityClass, $expected);
            } else {
                throw new Oxy_Test_PHPUnit_Exception(
                    sprintf('Child Entity must implement Oxy_Domain_AggregateRoot_ChildEntityInterface interface')
                );
            }
        }     
    }
    
    /**
     * @param Oxy_EventStore_EventProvider_EventProviderInterface $aggregateRoot
     * @param array $eventsToLoad
     */
    protected function _prepareAggregateRoot(
        Oxy_EventStore_EventProvider_EventProviderInterface $aggregateRoot, 
        array $eventsToLoad
    )
    {
        $collection = new Oxy_EventStore_Event_StorableEventsCollection();
        foreach ($eventsToLoad as $guid => $events){
            foreach ($events as $eventName => $event){
                $collection->addEvent(
                    new Oxy_EventStore_Event_StorableEvent(
                        new Oxy_Guid($guid),
                        $event
                    )
                );
            }
        }
        
        $aggregateRoot->loadEvents($collection);                
    }
}