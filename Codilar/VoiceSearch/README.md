# Voice search extension with hashtag for Magento 2 - BETA

Tired of manually typing for searching a product?
With this extension you will be able to:

* Voice Search
* You can search the product with related words
* You can get the result if you try search by hashtag as well

## Feature Guide

Introduction:
Voice search in e-commerce refers to the capability for customers to search for products and information within an online store using spoken language, typically through voice-activated devices like smartphones, smart speakers, or voice assistants. Instead of typing their search queries, users can use their voice to ask for specific products, categories, or information. This technology leverages speech recognition and natural language processing to understand and interpret the user's spoken words and provide relevant search results.

Benefits:
Convenience : Voice search simplifies the search process for users, allowing them to find products or information more quickly and easily by speaking their queries rather than typing them out.
Hands-Free Operation: Voice search enables users to interact with an ecommerce store while keeping their hands free, which is especially useful in situations where they may not be able to use a keyboard, such as when driving or cooking.
Accessibility: Voice search makes your ecommerce platform more accessible to users with disabilities or those who may have difficulty typing.
Natural Language Queries: Users can use natural language when conducting searches, making the interaction feel more conversational and reducing the need to use specific keywords.
Enhanced User Experience: Implementing voice search can enhance the overall user experience of your ecommerce store, making it more intuitive and modern.

API used to generate related words(that works with a neural network that analyzes thousands of wikipedia articles) : https://github.com/quiquelhappy/php-words-relationship

Solution Approach:
* Conversion of speech to text using JS API
* Generating random hashtag based on category, product name, description & saving that in a custom product attribute which is returning us strong search results even if you don’t know the actual product name
* Generating random related words for hashtag by using a neural network php library



## Install Guide 

Install the extension
```
put this module inside Codilar namespace directory
```

Verify if the extension is installed
```
bin/magento module:status
```

Enable the extension
```
bin/magento module:enable Codilar_VoiceSearch
```

Register the extension
```
bin/magento setup:upgrade
```

Recompile Magento project
```
bin/magento setup:di:compile
```

Clean cache
```
bin/magento cache:clean
```

## Additional Information

**To Enable the Microphone/Camera in Chrome for (Local) Unsecure Origins**

* Navigate to chrome://flags/#unsafely-treat-insecure-origin-as-secure in Chrome.

* Find and enable the Insecure origins treated as secure section.

* Add any addresses you want to ignore the secure origin policy for

* Save and restart Chrome.

**System Configuration Added In Admin To fetch Default Hash Tag Api**

![systemconfig](https://github.com/shakil-codilar/Codilar_Hackathon/assets/92923442/21d783c0-607c-4522-a38a-7741eb03ef95)


## Architecture Diagram

* Diagram  provides an illustrative overview of the key steps and decision points within our software's core process. It outlines the journey that data takes as it passes through different stages of our application.
  * Note: PDF is attached in the module directory








