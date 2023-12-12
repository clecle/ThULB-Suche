<?php

namespace EAH\Controller\Plugin;

use ThULB\Controller\Plugin\IlsRecords as OriginalIlsRecords;

class IlsRecords extends OriginalIlsRecords
{
    const ID_URI_PREFIX = 'http://uri.gbv.de/document/opac-de-j59:ppn:';
}