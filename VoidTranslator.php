<?php
namespace Illuminate\Validation;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Used as kind of «null pattern» Validator dependency for use Validator as standalone tool with minimum requirements.
 */
class VoidTranslator implements TranslatorInterface
{
    /**
     * Assume message id as message itself. Translates parameters in plain fashion:
     * <code>
     * $msg = $translator->trans('You have {number} friends', ['{number}' => 0]);
     * </code>
     *
     * @param string $id
     * @param array  $parameters
     * @param null   $domain
     * @param null   $locale
     *
     * @return string
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return strtr((string)$id, $parameters);
    }

    /**
     * There is no usage in Validator for now. This implementation just delegate to {@see trans()}
     *
     * @param string $id
     * @param int    $number
     * @param array  $parameters
     * @param null   $domain
     * @param null   $locale
     *
     * @return string
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->trans($id, $parameters);
    }

    /**
     * Locale has no matter in this context.
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        return $this;
    }

    /**
     * Locale has no matter in this context.
     * @return null|string
     */
    public function getLocale()
    {
        return null;
    }
}
